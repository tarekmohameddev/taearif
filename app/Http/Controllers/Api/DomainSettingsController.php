<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Domain\SetPrimaryDomainRequest;
use App\Http\Requests\Api\Domain\StoreDomainSettingRequest;
use App\Http\Requests\Api\Domain\VerifyDomainRequest;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DomainProvisioningService;
use App\Services\Vercel\DomainStatusSyncService;
use App\Services\Vercel\VercelDomainCache;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use App\Services\Vercel\VercelDomainInventoryService;
use App\Services\Vercel\VercelMutationGuard;
use App\Support\TenantActivity;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mail;

class DomainSettingsController extends Controller
{
    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly DomainProvisioningService $provisioningService,
        private readonly DomainStatusSyncService $domainSync,
        private readonly VercelDomainCache $vercelCache,
        private readonly VercelDomainInventoryService $vercelInventory,
        private readonly VercelMutationGuard $mutationGuard
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
        $customName = (string) $request->validated('custom_name');
        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);

        if ($autoAttach && ! $this->vercel->isConfigured()) {
            return response()->json([
                'success' => false,
                'code' => 'HOSTING_NOT_CONFIGURED',
                'message' => 'Domain hosting is not configured. Please contact support.',
            ], 503);
        }

        $existingDomain = ApiDomainSetting::query()
            ->where('custom_name', $customName)
            ->first();
        if ($existingDomain !== null) {
            return $this->duplicateDomainResponse($existingDomain, $user);
        }

        $domainsCount = ApiDomainSetting::query()->where('user_id', $user->id)->count();
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

        if (! $autoAttach) {
            $domain = $this->insertPendingDomainRow($user, $customName);
            if ($domain instanceof JsonResponse) {
                return $domain;
            }

            return $this->finalizeStoreResponse($request, $domain, null, [], 'skipped');
        }

        try {
            $provisioned = $this->provisionDomainWithVercel($user, $customName);
        } catch (LockTimeoutException $exception) {
            Log::warning('Timed out waiting for Vercel domain mutation lock', [
                'domain' => $customName,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'HOSTING_BUSY',
                'message' => 'Domain hosting is busy right now. Please try again shortly.',
            ], 503);
        }

        if ($provisioned instanceof JsonResponse) {
            return $provisioned;
        }

        return $this->finalizeStoreResponse(
            $request,
            $provisioned['domain'],
            $provisioned['provisionResult'] ?? null,
            $provisioned['syncResult'] ?? [],
            $provisioned['apex_attachment'] ?? 'unknown'
        );
    }

    /**
     * @return array{
     *     domain: ApiDomainSetting,
     *     provisionResult: array<string, mixed>,
     *     syncResult: array<string, mixed>,
     *     apex_attachment: string
     * }|JsonResponse
     */
    private function provisionDomainWithVercel(User $user, string $customName): array|JsonResponse
    {
        try {
            $this->mutationGuard->assertCanMutate();
        } catch (VercelDomainException $exception) {
            return $this->mapMutationGuardFailure($exception);
        }

        $inventory = $this->vercelCache->fresh();
        $capacity = $this->vercelInventory->evaluateCapacityForApex($inventory, $customName);

        if (! $capacity['allowed']) {
            return $this->capacityRejectedResponse($capacity['reason']);
        }

        $domain = $this->insertPendingDomainRow($user, $customName);
        if ($domain instanceof JsonResponse) {
            return $domain;
        }

        try {
            $provisionResult = $this->provisioningService->run(
                $customName,
                DomainProvisioningService::MODE_INITIAL
            );
        } catch (LockTimeoutException $exception) {
            Log::warning('Timed out waiting for Vercel domain mutation lock', [
                'domain' => $customName,
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'HOSTING_BUSY',
                'message' => 'Domain hosting is busy right now. Please try again shortly.',
            ], 503);
        }

        if (($provisionResult['outcome'] ?? '') === 'failed') {
            return $this->handleProvisioningStoreFailure($domain, $customName, $provisionResult);
        }

        $syncResult = $this->domainSync->applyProvisioningResult(
            $domain,
            $provisionResult,
            request(),
            applyFailureThreshold: false
        );
        $domain->refresh();
        $this->vercelCache->invalidateAdminCaches();

        return [
            'domain' => $domain,
            'provisionResult' => $provisionResult,
            'syncResult' => $syncResult,
            'apex_attachment' => (string) ($provisionResult['provisioning']['apex_attachment'] ?? 'unknown'),
        ];
    }

    private function insertPendingDomainRow(User $user, string $customName): ApiDomainSetting|JsonResponse
    {
        try {
            return DB::transaction(function () use ($user, $customName) {
                User::query()->whereKey($user->id)->lockForUpdate()->first();

                $existingDomain = ApiDomainSetting::query()
                    ->where('custom_name', $customName)
                    ->first();

                if ($existingDomain !== null) {
                    return $this->duplicateDomainResponse($existingDomain, $user);
                }

                $domainsCount = ApiDomainSetting::query()->where('user_id', $user->id)->count();
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

                $this->vercelCache->invalidateAdminCaches();

                return $domain;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }
            $existingDomain = ApiDomainSetting::query()
                ->where('custom_name', $customName)
                ->first();

            if ($existingDomain !== null) {
                return $this->duplicateDomainResponse($existingDomain, $user);
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>|null  $provisionResult
     * @param  array<string, mixed>  $syncResult
     */
    private function finalizeStoreResponse(
        Request $request,
        ApiDomainSetting $domain,
        ?array $provisionResult,
        array $syncResult,
        string $apexAttachment
    ): JsonResponse {
        if ($provisionResult === null) {
            $syncResult = $this->domainSync->sync($domain, true, $request);
            $domain->refresh();
            $this->vercelCache->invalidateAdminCaches();
        }

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
                ['old_status' => $syncResult['old_status'] ?? 'pending'],
                ['new_status' => 'active']
            );
        }

        $verified = $domain->status === 'active';
        $outcomePayload = $this->buildOutcomePayload($provisionResult, $syncResult);

        return response()->json(array_merge([
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
            'diagnostics' => $this->buildDiagnostics($domain, $provisionResult, $syncResult, $apexAttachment),
        ], $outcomePayload), 201);
    }

    /**
     * @param  array<string, mixed>|null  $provisionResult
     * @param  array<string, mixed>  $syncResult
     * @return array<string, mixed>
     */
    private function buildDiagnostics(
        ApiDomainSetting $domain,
        ?array $provisionResult,
        array $syncResult,
        string $apexAttachment
    ): array {
        $dnsRecords = is_array($domain->dns_records) ? $domain->dns_records : [];
        $provisioning = is_array($dnsRecords['provisioning'] ?? null) ? $dnsRecords['provisioning'] : [];
        $resolvedAttachment = $provisioning['apex_attachment'] ?? $apexAttachment;

        $lastCheck = is_array($dnsRecords['last_check'] ?? null) ? $dnsRecords['last_check'] : [];
        $ownershipChallenge = $lastCheck['ownership_challenge'] ?? null;
        $verificationRecords = $this->sanitizeVerificationRecords(
            is_array($ownershipChallenge) ? [$ownershipChallenge] : []
        );
        $verificationState = match (true) {
            (bool) ($syncResult['vercel_verified'] ?? false) && $domain->status === 'active' => 'verified',
            $domain->status === 'failed' => 'failed',
            default => 'pending',
        };

        return [
            'code' => $domain->status === 'active' ? 'DOMAIN_ACTIVE' : 'DOMAIN_PENDING',
            'apex_attachment' => $resolvedAttachment,
            'verification_state' => $verificationState,
            'recommended_dns' => ApiDomainSetting::nameserverInstructions(),
            'ownership_txt' => $verificationRecords,
            'outcome' => $provisionResult['outcome'] ?? ($syncResult['outcome'] ?? null),
            'health' => $provisionResult['health'] ?? ($syncResult['health_code'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $provisionResult
     * @param  array<string, mixed>  $syncResult
     * @return array{outcome: string, health: string, ssl: bool, retryable: bool, message: string}
     */
    private function buildOutcomePayload(?array $provisionResult, array $syncResult): array
    {
        if ($provisionResult !== null) {
            return [
                'outcome' => (string) ($provisionResult['outcome'] ?? 'pending'),
                'health' => (string) ($provisionResult['health'] ?? 'unchecked'),
                'ssl' => (bool) ($provisionResult['ssl'] ?? false),
                'retryable' => (bool) ($provisionResult['retryable'] ?? false),
                'message' => (string) ($provisionResult['message'] ?? ''),
            ];
        }

        $outcome = match ($syncResult['new_status'] ?? 'pending') {
            'active' => 'active',
            'failed' => 'failed',
            default => 'pending',
        };

        return [
            'outcome' => $outcome,
            'health' => (string) ($syncResult['health_code'] ?? 'unchecked'),
            'ssl' => (bool) ($syncResult['ssl'] ?? false),
            'retryable' => $outcome === 'pending' && ($syncResult['health_code'] ?? '') === 'provider_error',
            'message' => (string) ($syncResult['message'] ?? ''),
        ];
    }

    /**
     * @param  mixed  $verification
     * @return list<array<string, string>>
     */
    private function sanitizeVerificationRecords(mixed $verification): array
    {
        if (! is_array($verification)) {
            return [];
        }

        $items = array_is_list($verification) ? $verification : [$verification];
        $sanitized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $record = array_filter([
                'type' => isset($item['type']) ? (string) $item['type'] : null,
                'domain' => isset($item['domain']) ? strtolower((string) $item['domain']) : null,
                'value' => isset($item['value']) ? (string) $item['value'] : null,
                'reason' => isset($item['reason']) ? (string) $item['reason'] : null,
            ], fn ($value) => $value !== null && $value !== '');

            if ($record !== []) {
                $sanitized[] = $record;
            }
        }

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>  $provisionResult
     */
    private function handleProvisioningStoreFailure(
        ApiDomainSetting $domain,
        string $customName,
        array $provisionResult
    ): JsonResponse {
        $health = (string) ($provisionResult['health'] ?? 'provider_error');
        $provisioning = is_array($provisionResult['provisioning'] ?? null)
            ? $provisionResult['provisioning']
            : [];
        $retryable = (bool) ($provisionResult['retryable'] ?? false);

        if ($retryable && $health === 'provider_error') {
            $pendingResult = array_merge($provisionResult, [
                'outcome' => 'pending',
                'message' => 'Hosting provider state is uncertain. Retry verification shortly.',
            ]);
            $syncResult = $this->domainSync->applyProvisioningResult(
                $domain,
                $pendingResult,
                request(),
                applyFailureThreshold: false
            );
            $domain->refresh();
            $this->vercelCache->invalidateAdminCaches();

            return $this->finalizeStoreResponse(
                request(),
                $domain,
                $pendingResult,
                $syncResult,
                (string) ($provisioning['apex_attachment'] ?? 'uncertain')
            );
        }

        $this->vercelCache->invalidateAdminCaches();

        Log::error('Failed to provision domain with hosting provider', [
            'domain' => $customName,
            'domain_id' => $domain->id,
            'health' => $health,
            'provisioning' => $provisioning,
        ]);

        $capacityReason = $provisioning['capacity_reason'] ?? null;
        $internalCode = $provisioning['internal_code'] ?? null;
        if ($capacityReason === 'capacity_reached'
            || $internalCode === VercelDomainException::CODE_CAPACITY_REACHED) {
            $domain->delete();
            $this->vercelCache->invalidateAdminCaches();

            return response()->json([
                'success' => false,
                'code' => 'HOSTING_CAPACITY_REACHED',
                'message' => 'We cannot add more domains right now because the hosting limit has been reached. Please contact support.',
            ], 503);
        }

        if ($health === 'ownership_required') {
            $domain->delete();
            $this->vercelCache->invalidateAdminCaches();

            return response()->json([
                'success' => false,
                'code' => 'DOMAIN_OWNERSHIP_REQUIRED',
                'message' => 'This domain is already registered elsewhere. Complete the ownership verification steps and try again.',
            ], 409);
        }

        $internalCode = $provisioning['internal_code'] ?? null;
        $mapped = match ($internalCode) {
            VercelDomainException::CODE_MUTATION_BLOCKED,
            VercelDomainException::CODE_NOT_CONFIGURED,
            VercelDomainException::CODE_PROJECT_IDENTITY_MISMATCH => [
                'status' => 503,
                'code' => 'HOSTING_NOT_CONFIGURED',
                'message' => 'Domain hosting is not configured. Please contact support.',
            ],
            default => null,
        };

        $domain->delete();
        $this->vercelCache->invalidateAdminCaches();

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

    private function capacityRejectedResponse(?string $reason): JsonResponse
    {
        if ($reason === 'capacity_reached') {
            return response()->json([
                'success' => false,
                'code' => 'HOSTING_CAPACITY_REACHED',
                'message' => 'We cannot add more domains right now because the hosting limit has been reached. Please contact support.',
            ], 503);
        }

        return response()->json([
            'success' => false,
            'code' => 'HOSTING_INVENTORY_UNAVAILABLE',
            'message' => 'Domain hosting capacity could not be confirmed right now. Please try again shortly.',
        ], 503);
    }

    private function mapMutationGuardFailure(VercelDomainException $exception): JsonResponse
    {
        $code = match ($exception->internalCode) {
            VercelDomainException::CODE_MUTATION_BLOCKED => 'HOSTING_MUTATION_BLOCKED',
            VercelDomainException::CODE_PROJECT_IDENTITY_MISMATCH => 'HOSTING_IDENTITY_MISMATCH',
            default => 'HOSTING_NOT_CONFIGURED',
        };

        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => 'Domain hosting is not available in this environment. Please contact support.',
        ], 503);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $driverCode = (string) ($exception->errorInfo[1] ?? '');
        if (in_array($driverCode, ['1062', '19', '23505'], true)) {
            return true;
        }
        return str_contains(strtolower($exception->getMessage()), 'unique');
    }
    private function duplicateDomainResponse(ApiDomainSetting $existingDomain, User $user): JsonResponse
    {
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

        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);
        $mode = in_array($domain->status, ['pending', 'failed'], true)
            ? DomainProvisioningService::MODE_SCHEDULED
            : null;

        try {
            $provisionResult = $this->provisioningService->run($apex, $mode);
        } catch (LockTimeoutException $exception) {
            Log::warning('Timed out waiting for Vercel domain mutation lock during verify', [
                'domain' => $apex,
                'domain_id' => $domain->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'HOSTING_BUSY',
                'message' => 'Domain hosting is busy right now. Please try again shortly.',
            ], 503);
        }

        $result = $this->domainSync->applyProvisioningResult(
            $domain,
            $provisionResult,
            $request,
            applyFailureThreshold: false
        );
        $domain->refresh();
        $this->vercelCache->invalidateAdminCaches();

        $outcomePayload = $this->buildOutcomePayload($provisionResult, $result);

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

            return response()->json(array_merge([
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
            ], $outcomePayload));
        }

        if ($result['new_status'] === 'failed') {
            return response()->json(array_merge([
                'success' => false,
                'message' => $result['message'] ?: 'Domain verification failed',
                'data' => [
                    'id' => $domain->id,
                    'custom_name' => $domain->custom_name,
                    'status' => $domain->status,
                    'verificationStatus' => 'failed',
                    'message' => $result['message'],
                ],
            ], $outcomePayload), 422);
        }

        return response()->json(array_merge([
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
        ], $outcomePayload), 422);
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
        $this->vercelCache->invalidateAdminCaches();

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
        $adminEmail = config('mail.admin_address');
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
}
