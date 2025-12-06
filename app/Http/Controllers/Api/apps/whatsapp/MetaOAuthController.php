<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappUser;
use App\Services\MetaGraphService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MetaOAuthController extends Controller
{
    protected MetaGraphService $metaGraph;

    public function __construct(MetaGraphService $metaGraph)
    {
        $this->metaGraph = $metaGraph;
    }

    /**
     * Endpoint A — redirect to Meta Embedded Signup to start onboarding.
     *
     * Query parameters:
     *   - mode: "new" (default) or "existing" to connect an existing WABA
     *
     * This uses Facebook Login for Business with Embedded Signup extras.
     * See: https://developers.facebook.com/docs/whatsapp/embedded-signup
     */
    public function redirect(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $appId = Config::get('services.meta.app_id');
        $redirectUri = Config::get('services.meta.redirect_uri');
        $configId = Config::get('services.meta.embedded_signup_config_id');
        $apiVersion = Config::get('services.meta.api_version', 'v20.0');

        if (!$appId) {
            return response()->json([
                'success' => false,
                'message' => 'Meta app configuration is missing (app_id).',
            ], 500);
        }

        if (!$configId) {
            return response()->json([
                'success' => false,
                'message' => 'Meta Embedded Signup config_id is required. Set META_EMBEDDED_SIGNUP_CONFIG_ID in .env',
            ], 500);
        }

        // Determine mode: "new" (create new WABA) or "existing" (connect existing)
        $mode = $request->query('mode', 'new');
        $validModes = ['new', 'existing'];
        if (!in_array($mode, $validModes, true)) {
            $mode = 'new';
        }

        // Build encrypted state with user context and mode
        $statePayload = [
            'user_id' => $user->id,
            'mode' => $mode,
            'issued_at' => now()->timestamp,
        ];

        $state = Crypt::encryptString(json_encode($statePayload));

        // Build extras for Embedded Signup (Meta's Embedded Signup format)
        // featureType determines the onboarding flow
        $extras = [
            'featureType' => 'whatsapp_business_app_onboarding',
            'sessionInfoVersion' => '3',
            'version' => 'v3',
        ];

        // For existing WABA onboarding, add setup hints
        if ($mode === 'existing') {
            $extras['setup'] = [
                'allowExisting' => true,
            ];
        }

        $queryParams = [
            'app_id' => $appId,
            'client_id' => $appId,
            'config_id' => $configId,
            'response_type' => 'code',
            'override_default_response_type' => 'true',
            'extras' => json_encode($extras),
        ];

        // Add redirect_uri if configured (optional for Embedded Signup with config_id)
        if ($redirectUri) {
            $queryParams['redirect_uri'] = $redirectUri;
            $queryParams['state'] = $state;
        }

        $query = http_build_query($queryParams);

        $url = "https://www.facebook.com/{$apiVersion}/dialog/oauth?{$query}";

        return response()->json([
            'success' => true,
            'redirect_url' => $url,
            'mode' => $mode,
            'config_id' => $configId,
        ]);
    }

    /**
     * Callback endpoint — receives code, exchanges it for a
     * short-lived user access token, optionally upgrades to a
     * long-lived token, and fetches business/phone info.
     *
     * If multiple businesses/WABAs/phones exist, returns all for frontend selection.
     */
    public function callback(Request $request)
    {
        $error = $request->query('error');
        if ($error) {
            Log::warning('MetaOAuthController.callback received error from Meta', [
                'error' => $error,
                'error_reason' => $request->query('error_reason'),
                'error_description' => $request->query('error_description'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $error,
                'error_reason' => $request->query('error_reason'),
                'error_description' => $request->query('error_description'),
            ], 400);
        }

        $code = $request->query('code');
        $state = $request->query('state');

        if (!$code || !$state) {
            return response()->json([
                'success' => false,
                'message' => 'Missing authorization code or state.',
            ], 400);
        }

        // Decrypt state to recover user context
        try {
            $decoded = json_decode(Crypt::decryptString($state), true);
        } catch (DecryptException $e) {
            Log::error('MetaOAuthController.callback state decryption failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid state parameter.',
            ], 400);
        }

        if (!is_array($decoded) || empty($decoded['user_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid state payload.',
            ], 400);
        }

        $userId = (int) $decoded['user_id'];
        $mode = $decoded['mode'] ?? 'new';

        try {
            // 1) Exchange code for short-lived token
            $tokenResponse = $this->metaGraph->exchangeCodeForToken($code);
            $shortLivedToken = $tokenResponse['access_token'] ?? null;
            $expiresIn = $tokenResponse['expires_in'] ?? null;

            if (!$shortLivedToken) {
                throw new \RuntimeException('No access_token in Meta response.');
            }

            // 2) Optionally upgrade to long-lived token
            $finalToken = $shortLivedToken;
            $expiresAt = null;

            try {
                $longLived = $this->metaGraph->exchangeForLongLivedToken($shortLivedToken);
                if (!empty($longLived['access_token'])) {
                    $finalToken = $longLived['access_token'];
                    $longExpiresIn = $longLived['expires_in'] ?? null;
                    if ($longExpiresIn) {
                        $expiresAt = Carbon::now()->addSeconds((int) $longExpiresIn);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('MetaOAuthController.callback long-lived token exchange failed', [
                    'error' => $e->getMessage(),
                ]);

                if ($expiresIn) {
                    $expiresAt = Carbon::now()->addSeconds((int) $expiresIn);
                }
            }

            // 3) Use token to list ALL businesses
            $businessesResponse = $this->metaGraph->listBusinesses($finalToken);
            $businesses = $businessesResponse['data'] ?? [];

            if (empty($businesses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No businesses found for the user.',
                    'raw' => $businessesResponse,
                ], 400);
            }

            // 4) List ALL WABA accounts across all businesses
            $allWabas = [];
            foreach ($businesses as $business) {
                $businessId = $business['id'] ?? null;
                $businessName = $business['name'] ?? 'Unknown';

                if (!$businessId) {
                    continue;
                }

                try {
                    $wabaResponse = $this->metaGraph->listWhatsAppBusinessAccounts($finalToken, $businessId);
                    $wabas = $wabaResponse['data'] ?? [];

                    foreach ($wabas as $waba) {
                        $wabaId = $waba['id'] ?? null;
                        if (!$wabaId) {
                            continue;
                        }

                        // 5) List ALL phone numbers for each WABA
                        $phonesResponse = $this->metaGraph->listPhoneNumbers($finalToken, $wabaId);
                        $phones = $phonesResponse['data'] ?? [];

                        $allWabas[] = [
                            'business_id' => $businessId,
                            'business_name' => $businessName,
                            'waba_id' => $wabaId,
                            'waba_name' => $waba['name'] ?? null,
                            'phones' => array_map(function ($phone) {
                                return [
                                    'phone_number_id' => $phone['id'] ?? null,
                                    'display_phone_number' => $phone['display_phone_number'] ?? null,
                                    'verified_name' => $phone['verified_name'] ?? null,
                                    'quality_rating' => $phone['quality_rating'] ?? null,
                                ];
                            }, $phones),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::warning('MetaOAuthController.callback failed to fetch WABAs for business', [
                        'business_id' => $businessId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (empty($allWabas)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No WhatsApp Business Accounts found across any business.',
                    'businesses' => $businesses,
                ], 400);
            }

            // If only one WABA with one phone, auto-select and persist
            $autoSelected = false;
            $selectedData = null;

            if (count($allWabas) === 1 && count($allWabas[0]['phones']) === 1) {
                $autoSelected = true;
                $waba = $allWabas[0];
                $phone = $waba['phones'][0];

                $whatsappUser = WhatsappUser::updateOrCreate(
                    ['user_id' => $userId],
                    [
                        'number' => $phone['display_phone_number'],
                        'name' => $phone['verified_name'],
                        'status' => 'active',
                        'request_status' => 'active',
                        'token' => $finalToken,
                        'access_token' => $finalToken,
                        'token_expires_at' => $expiresAt,
                        'business_id' => $waba['business_id'],
                        'waba_id' => $waba['waba_id'],
                        'phone_id' => $phone['phone_number_id'],
                    ]
                );

                $selectedData = [
                    'business_id' => $waba['business_id'],
                    'business_name' => $waba['business_name'],
                    'waba_id' => $waba['waba_id'],
                    'waba_name' => $waba['waba_name'],
                    'phone_number_id' => $phone['phone_number_id'],
                    'display_phone_number' => $phone['display_phone_number'],
                    'verified_name' => $phone['verified_name'],
                    'whatsapp_user_id' => $whatsappUser->id,
                ];
            }

            // Store token temporarily for selection flow (if multiple options)
            if (!$autoSelected) {
                // Store token in session or cache for subsequent selection call
                $tempKey = 'meta_signup_' . $userId;
                cache()->put($tempKey, [
                    'token' => $finalToken,
                    'expires_at' => $expiresAt,
                ], now()->addMinutes(15));
            }

            return response()->json([
                'success' => true,
                'auto_selected' => $autoSelected,
                'message' => $autoSelected
                    ? 'WhatsApp Business account linked successfully via Meta Embedded Signup.'
                    : 'Multiple WhatsApp Business Accounts found. Please select one.',
                'mode' => $mode,
                'token_expires_at' => optional($expiresAt)->toIso8601String(),
                'selected' => $selectedData,
                'available' => $autoSelected ? null : $allWabas,
            ]);
        } catch (\Throwable $e) {
            Log::error('MetaOAuthController.callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete Meta Embedded Signup flow.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Select and persist a specific WABA/phone after callback returned multiple options.
     *
     * POST /api/whatsapp/meta/select
     * Body: { "business_id": "...", "waba_id": "...", "phone_number_id": "...", "display_phone_number": "...", "verified_name": "..." }
     */
    public function select(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $validated = $request->validate([
            'business_id' => 'required|string',
            'waba_id' => 'required|string',
            'phone_number_id' => 'required|string',
            'display_phone_number' => 'nullable|string',
            'verified_name' => 'nullable|string',
        ]);

        // Retrieve cached token from signup flow
        $tempKey = 'meta_signup_' . $user->id;
        $cached = cache()->get($tempKey);

        if (!$cached || empty($cached['token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Signup session expired. Please restart the Meta Embedded Signup flow.',
            ], 400);
        }

        $whatsappUser = WhatsappUser::updateOrCreate(
            ['user_id' => $user->id],
            [
                'number' => $validated['display_phone_number'] ?? null,
                'name' => $validated['verified_name'] ?? null,
                'status' => 'active',
                'request_status' => 'active',
                'token' => $cached['token'],
                'access_token' => $cached['token'],
                'token_expires_at' => $cached['expires_at'] ?? null,
                'business_id' => $validated['business_id'],
                'waba_id' => $validated['waba_id'],
                'phone_id' => $validated['phone_number_id'],
            ]
        );

        // Clear cached token
        cache()->forget($tempKey);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp Business account selected and linked successfully.',
            'data' => [
                'business_id' => $validated['business_id'],
                'waba_id' => $validated['waba_id'],
                'phone_number_id' => $validated['phone_number_id'],
                'display_phone_number' => $validated['display_phone_number'],
                'verified_name' => $validated['verified_name'],
                'whatsapp_user_id' => $whatsappUser->id,
            ],
        ]);
    }
}


