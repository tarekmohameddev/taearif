<?php

namespace App\Http\Controllers\Api;

use Log;
use Mail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiDomainSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DomainSettingsController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();
        $domains = $user->domains;


        return response()->json([
            'domains' => $domains->map(function ($domain) {
            return [
                'id' => $domain->id,
                'custom_name' => $domain->custom_name,
                'status' => $domain->status,
                'primary' => $domain->primary,
                'ssl' => $domain->ssl,
                'addedDate' => $domain->added_date->format('Y-m-d'),
            ];
            }),
            'dnsInstructions' => [
            'records' => [
                [
                'type' => 'A',
                'name' => '@',
                'value' => '76.76.21.21',
                'ttl' => 3600,
                ],
                [
                'type' => 'CNAME',
                'name' => 'www',
                'value' => $user->id . 'taearif.com',
                'ttl' => 3600,
                ],
            ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'custom_name' => 'required|string|max:255|regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i',
        ]);
        $existingDomain = ApiDomainSetting::where('custom_name', $validated['custom_name'])->where('user_id', $user->id)->first();
        if ($existingDomain) {
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
        $domainsCount = ApiDomainSetting::where('user_id', $user->id)->count();
        $domain = new ApiDomainSetting([
            'user_id' => $user->id,
            'custom_name' => $validated['custom_name'],
            'status' => 'pending',
            'primary' => $domainsCount === 0, // First domain is primary by default
            'ssl' => false,
            'added_date' => now(),

        ]);
        $domain->save();

        return response()->json([
            'success' => true,
            'message' => 'Domain added successfully',
            'data' => [
            'id' => $domain->id,
            'custom_name' => $domain->custom_name,
            'status' => $domain->status,
            'primary' => $domain->primary,
            'ssl' => $domain->ssl,
            'addedDate' => $domain->added_date->format('Y-m-d'),
            ],
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
            'addedDate' => $domain->added_date->format('Y-m-d'),
            'dnsInstructions' => [
            'records' => $domain->getDnsRecords(),
            ],
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

        // If this is the primary domain, set another domain as primary
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

        $domain->delete();

        return response()->json([
            'success' => true,
            'message' => 'Domain deleted successfully',
        ]);
    }

    /* verify domain */
    public function verify(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'id' => 'required|integer|exists:api_domains_settings,id',
        ]);

        $domain = ApiDomainSetting::where('id', $validated['id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($domain->status !== 'active') {
            $domain->status = 'active';
            $domain->save();


            $this->notifyAdminOfVerifiedDomain($domain); // Notify admin
        }
        return response()->json([
            'success' => true,
            'message' => 'Domain verification initiated',
            'data' => [
                'id' => $domain->id,
                'custom_name' => $domain->custom_name,
                'status' => $domain->status,
                'verificationStatus' => 'in_progress',
                'estimatedTime' => '1-2 minutes',
            ],
        ]);
    }

    /* set primary domain */
    public function setPrimary(Request $request)
    {

        $user = Auth::user();

        $validated = $request->validate([
            'id' => 'required|integer|exists:api_domains_settings,id',
        ]);

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
                'addedDate' => $domain->added_date->format('Y-m-d'),
            ];
            }),
        ],
        ]);



    }

    /* Notify admin of verified domain */
    private function notifyAdminOfVerifiedDomain(ApiDomainSetting $domain)
    {
        $adminEmail = env('MAIL_ADMIN_ADDRESS', 'admin@example.com'); // from .env
        if (!$adminEmail) {
            Log::error("Failed to send admin domain verification email: Admin email not set in .env");
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
            Log::error("Failed to send admin domain verification email: " . $e->getMessage());
        }
    }

    /* user request to enable the ssl */

    public function requestSsl(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'id' => 'required|integer|exists:api_domains_settings,id',
        ]);

        $domain = ApiDomainSetting::where('id', $validated['id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($domain->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Domain must be active before requesting SSL.',
            ], 400);
        }

        if ($domain->ssl) {
            return response()->json([
                'success' => false,
                'message' => 'SSL is already enabled for this domain.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'SSL request submitted. It will be provisioned shortly.',
        ]);
    }

    /* admin update ssl status */

    public function updateSslStatus(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|exists:api_domains_settings,id',
            'ssl' => 'required|boolean',
        ]);

        $domain = ApiDomainSetting::findOrFail($request->domain_id);
        $domain->ssl = $request->ssl;
        $domain->save();

        return response()->json([
            'success' => true,
            'message' => 'SSL status updated successfully.',
            'data' => [
                'id' => $domain->id,
                'custom_name' => $domain->requested_domain ?? $domain->custom_name,
                'ssl' => $domain->ssl,
            ],
        ]);
    }



}
