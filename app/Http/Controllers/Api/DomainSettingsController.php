<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Domain\RequestDomainSslRequest;
use App\Http\Requests\Api\Domain\SetPrimaryDomainRequest;
use App\Http\Requests\Api\Domain\StoreDomainSettingRequest;
use App\Http\Requests\Api\Domain\VerifyDomainRequest;
use App\Models\Api\ApiDomainSetting;
use App\Services\Vercel\DomainStatusSyncService;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use App\Support\TenantActivity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Mail;

class DomainSettingsController extends Controller
{
    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly DomainStatusSyncService $domainSync
    ) {
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains()->select(['id', 'custom_name', 'status', 'primary', 'ssl', 'added_date'])->get();

        return response()->json([
            'domains' => $domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'custom_name' => $domain->custom_name,
                    'status' => $domain->status,
                    'primary' => $domain->primary,
                    'ssl' => $domain->ssl,
                    'addedDate' => $domain->added_date?->format('Y-m-d'),
                ];
            }),
            'dnsInstructions' => ApiDomainSetting::nameserverInstructions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDomainSettingRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();
        $customName = $this->vercel->normalizeApex($validated['custom_name']);

        $existingDomain = ApiDomainSetting::where('custom_name', $customName)->first();
        if ($existingDomain) {
            if ((int) $existingDomain->user_id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain already exists',
                    'errors' => [
                        [
                            'field' => 'custom_name',
                            'message' => 'This domain is already added to your account',
                        ],
                    ],
                ], 400);
            }

            return response()->json([
                'success' => false,
                'message' => 'Domain already in use',
                'errors' => [
                    [
                        'field' => 'custom_name',
                        'message' => 'This domain is already in use',
                    ],
                ],
            ], 400);
        }

        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);

        if ($autoAttach && ! $this->vercel->isConfigured()) {
            return response()->json([
                'success' => false,
                'code' => 'HOSTING_NOT_CONFIGURED',
                'message' => 'Domain hosting is not configured. Please contact support.',
            ], 503);
        }

        $domainsCount = ApiDomainSetting::where('user_id', $user->id)->count();
        $maxDomains = max(1, (int) config('services.vercel.max_domains_per_tenant', 5));
        if ($domainsCount >= $maxDomains) {
            return response()->json([
                'success' => false,
                'message' => 'Domain limit reached',
                'errors' => [
                    [
                        'field' => 'custom_name',
                        'message' => "You can add up to {$maxDomains} domains.",
                    ],
                ],
            ], 400);
        }

        $domain = new ApiDomainSetting([
            'user_id' => $user->id,
            'custom_name' => $customName,
            'status' => 'pending',
            'primary' => $domainsCount === 0,
            'ssl' => false,
            'added_date' => now(),
        ]);
        $domain->save();

        if ($autoAttach) {
            try {
                $this->vercel->addApexWithWwwRedirect($customName);
            } catch (VercelDomainException $e) {
                // The apex may already be attached when the www call is what failed.
                // Best effort: a cleanup failure must never mask the original error.
                try {
                    $this->vercel->removeApexAndWww($customName);
                } catch (VercelDomainException $cleanupError) {
                    Log::warning('Could not detach partially attached domain after a failed add', [
                        'domain' => $customName,
                        'error' => $cleanupError->getMessage(),
                    ]);
                }

                $domain->delete();
                Log::error('Failed to attach domain to Vercel', [
                    'domain' => $customName,
                    'error' => $e->getMessage(),
                    'status' => $e->statusCode,
                    'vercel_error_code' => $e->getErrorCode(),
                ]);

                $mapped = match ($e->getErrorCode()) {
                    'project_domain_limit_reached' => [
                        'status' => 503,
                        'code' => 'HOSTING_CAPACITY_REACHED',
                        'message' => 'We cannot add more domains right now because the hosting limit has been reached. Please contact support.',
                    ],
                    default => null,
                };

                if ($mapped !== null) {
                    return response()->json([
                        'success' => false,
                        'code' => $mapped['code'],
                        'message' => $mapped['message'],
                    ], $mapped['status']);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to register domain with hosting provider. Please try again later.',
                ], 502);
            }
        }

        // Immediate verify + NS check so the dashboard can show success or setup guidance
        $syncResult = $this->domainSync->sync($domain, true, $request);
        $domain->refresh();

        TenantActivity::emit(
            $request,
            'domain.added',
            'api_domains_settings',
            $domain->id,
            null,
            $domain->only(['custom_name', 'status', 'primary', 'ssl'])
        );

        if ($domain->status === 'active') {
            $this->notifyAdminOfVerifiedDomain($domain);
            TenantActivity::emit(
                $request,
                'domain.verified',
                'api_domains_settings',
                $domain->id,
                ['old_status' => $syncResult['old_status']],
                ['new_status' => 'active']
            );
        }

        $verified = $domain->status === 'active';

        return response()->json([
            'success' => true,
            'message' => 'Domain added successfully',
            'data' => [
                'id' => $domain->id,
                'custom_name' => $domain->custom_name,
                'status' => $domain->status,
                'primary' => $domain->primary,
                'ssl' => $domain->ssl,
                'addedDate' => $domain->added_date?->format('Y-m-d'),
            ],
            'verification' => [
                'verified' => $verified,
                'nameservers_ok' => (bool) ($syncResult['nameservers_ok'] ?? false),
                'status' => $domain->status,
                'message' => $syncResult['message'] ?? (
                    $verified
                        ? 'Domain is verified and nameservers are correct.'
                        : 'Nameservers are not pointing to Vercel yet.'
                ),
            ],
            'dnsInstructions' => ApiDomainSetting::nameserverInstructions(),
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::user();
        $domain = ApiDomainSetting::where('id', $id)->where('user_id', $user->id)->firstOrFail();

        return response()->json([
            'id' => $domain->id,
            'custom_name' => $domain->custom_name,
            'status' => $domain->status,
            'primary' => $domain->primary,
            'ssl' => $domain->ssl,
            'addedDate' => $domain->added_date?->format('Y-m-d'),
            'dnsInstructions' => ApiDomainSetting::nameserverInstructions(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = Auth::user();

        try {
            $domain = ApiDomainSetting::where('id', $id)
                ->where('user_id', $user->id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Domain not found or you do not have permission to delete it.',
            ], 404);
        }

        if ($domain->primary) {
            $anotherDomain = ApiDomainSetting::where('user_id', $user->id)
                ->where('id', '!=', $domain->id)
                ->where('status', 'active')
                ->first();

            if ($anotherDomain) {
                $anotherDomain->primary = true;
                $anotherDomain->save();
            }
        }

        if ((bool) config('services.vercel.auto_attach_custom_domain', true) && $this->vercel->isConfigured()) {
            try {
                $this->vercel->removeApexAndWww((string) $domain->custom_name);
            } catch (VercelDomainException $e) {
                Log::warning('Failed to remove domain from Vercel during destroy', [
                    'domain_id' => $domain->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to remove domain from hosting provider. Please try again later.',
                ], 502);
            }
        }

        $domain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domain deleted successfully',
        ]);
    }

    public function verify(VerifyDomainRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $domain = ApiDomainSetting::where('id', $validated['id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);
        $checkNameservers = (bool) config('services.vercel.check_nameservers', true);

        if ($autoAttach && ! $this->vercel->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Domain hosting is not configured. Please contact support.',
            ], 503);
        }

        if (! $autoAttach && ! $checkNameservers) {
            return response()->json([
                'success' => false,
                'message' => 'Verification checks are disabled. Please contact support.',
            ], 503);
        }

        $result = $this->domainSync->sync($domain, true, $request);
        $domain->refresh();

        if ($result['new_status'] === 'active') {
            if ($result['changed'] || $result['old_status'] !== 'active') {
                $this->notifyAdminOfVerifiedDomain($domain);
            }

            TenantActivity::emit(
                $request,
                'domain.verified',
                'api_domains_settings',
                $domain->id,
                ['old_status' => $result['old_status']],
                ['new_status' => 'active']
            );

            return response()->json([
                'success' => true,
                'message' => 'Domain verified successfully',
                'data' => [
                    'id' => $domain->id,
                    'custom_name' => $domain->custom_name,
                    'status' => $domain->status,
                    'ssl' => $domain->ssl,
                    'verificationStatus' => 'verified',
                    'message' => $result['message'],
                ],
            ]);
        }

        if ($result['new_status'] === 'failed') {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?: 'Domain verification failed',
                'data' => [
                    'id' => $domain->id,
                    'custom_name' => $domain->custom_name,
                    'status' => $domain->status,
                    'verificationStatus' => 'failed',
                    'message' => $result['message'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?: 'Domain verification is still pending',
            'data' => [
                'id' => $domain->id,
                'custom_name' => $domain->custom_name,
                'status' => $domain->status,
                'verificationStatus' => 'pending',
                'message' => $result['message'],
                'dnsInstructions' => ApiDomainSetting::nameserverInstructions(),
            ],
        ], 422);
    }

    public function setPrimary(SetPrimaryDomainRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $domain = ApiDomainSetting::where('id', $validated['id'])->where('user_id', $user->id)->firstOrFail();

        if ($domain->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set pending domain as primary',
                'errors' => [
                    [
                        'field' => 'id',
                        'message' => 'Domain must be active to be set as primary',
                    ],
                ],
            ], 400);
        }

        ApiDomainSetting::where('user_id', $user->id)->update(['primary' => false]);
        $domain->primary = true;
        $domain->save();

        $domains = $user->domains()->get();

        TenantActivity::emit($request, 'domain.set_primary', 'api_domains_settings', $domain->id);

        return response()->json([
            'success' => true,
            'message' => 'Primary domain updated successfully',
            'data' => [
                'domains' => $domains->map(function ($domain) {
                    return [
                        'id' => $domain->id,
                        'custom_name' => $domain->custom_name,
                        'status' => $domain->status,
                        'primary' => $domain->primary,
                        'ssl' => $domain->ssl,
                        'addedDate' => $domain->added_date?->format('Y-m-d'),
                    ];
                }),
            ],
        ]);
    }

    private function notifyAdminOfVerifiedDomain(ApiDomainSetting $domain)
    {
        $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@example.com');
        if (! $adminEmail) {
            Log::error('Failed to send admin domain verification email: Admin email not set in .env');

            return;
        }

        $user = $domain->user;

        $subject = " Domain Verified: {$domain->custom_name}";
        $message = "
            A user has verified a domain on your platform:

            - User: {$user->username} ({$user->email})
            - Domain: {$domain->custom_name}
            - Date: " . now()->toDateTimeString() . "

            You can review it in the admin panel.
        ";

        try {
            Mail::raw($message, function ($mail) use ($adminEmail, $subject) {
                $mail->to($adminEmail)
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send admin domain verification email: ' . $e->getMessage());
        }
    }

    public function requestSsl(RequestDomainSslRequest $request)
    {
        $user = Auth::user();
        $validated = $request->validated();

        $domain = ApiDomainSetting::where('id', $validated['id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($domain->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Domain must be active before requesting SSL.',
            ], 400);
        }

        $result = $this->domainSync->sync($domain, true, $request);
        $domain->refresh();

        TenantActivity::emit($request, 'domain.ssl_requested', 'api_domains_settings', $domain->id);

        if ($domain->ssl) {
            return response()->json([
                'success' => true,
                'message' => 'SSL is enabled for this domain.',
                'data' => [
                    'id' => $domain->id,
                    'ssl' => true,
                    'status' => $domain->status,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?: 'SSL is not ready yet. Please try again shortly.',
            'data' => [
                'id' => $domain->id,
                'ssl' => false,
                'status' => $domain->status,
            ],
        ], 422);
    }
}
