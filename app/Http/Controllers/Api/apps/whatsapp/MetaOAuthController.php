<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappUserToWaNumberService;
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

    public function __construct(
        MetaGraphService $metaGraph,
        private readonly SyncWhatsappUserToWaNumberService $syncWaNumber,
    ) {
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
     * Callback endpoint — receives code from Meta Embedded Signup,
     * exchanges it for an access token, fetches the linked WABA/phone,
     * and saves directly to the database.
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

        try {
            // 1) Exchange code for short-lived token
            $tokenResponse = $this->metaGraph->exchangeCodeForToken($code);
            $shortLivedToken = $tokenResponse['access_token'] ?? null;
            $expiresIn = $tokenResponse['expires_in'] ?? null;

            if (!$shortLivedToken) {
                throw new \RuntimeException('No access_token in Meta response.');
            }

            // 2) Upgrade to long-lived token
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

            // 3) Debug token to get WABA ID from granular scopes
            $debugTokenResponse = $this->metaGraph->debugToken($finalToken);
            $wabaId = $this->metaGraph->extractWabaIdFromDebugToken($debugTokenResponse);

            if (!$wabaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No WhatsApp Business Account found in token scopes.',
                ], 400);
            }

            Log::info('MetaOAuthController.callback WABA ID extracted', [
                'waba_id' => $wabaId,
            ]);

            // 4) Get phone numbers for that WABA
            $phonesResponse = $this->metaGraph->listPhoneNumbers($finalToken, $wabaId);
            $phones = $phonesResponse['data'] ?? [];

            if (empty($phones)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No phone number found for WhatsApp Business Account.',
                ], 400);
            }

            // Get existing phone_ids for this user to find the newly added one
            $existingPhoneIds = WhatsappUser::where('user_id', $userId)
                ->whereNotNull('phone_id')
                ->pluck('phone_id')
                ->toArray();

            // Find the newly added phone (one that doesn't exist in our database yet)
            $newPhone = null;
            foreach ($phones as $phone) {
                $phoneId = $phone['id'] ?? null;
                if ($phoneId && !in_array($phoneId, $existingPhoneIds)) {
                    $newPhone = $phone;
                    break;
                }
            }

            // If no new phone found (all phones already exist), use the last phone in the list
            // This handles the case where user re-authorizes an existing phone
            if (!$newPhone) {
                $newPhone = end($phones);
            }

            $phoneId = $newPhone['id'] ?? null;
            $displayPhoneNumber = $newPhone['display_phone_number'] ?? null;
            $verifiedName = $newPhone['verified_name'] ?? null;

            if (!$phoneId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number data from Meta.',
                ], 400);
            }

            // 5) Subscribe app to WABA for webhooks
            try {
                $this->metaGraph->subscribeAppToWaba($finalToken, $wabaId);
            } catch (\Throwable $e) {
                Log::warning('MetaOAuthController.callback WABA subscription failed (non-fatal)', [
                    'waba_id' => $wabaId,
                    'error' => $e->getMessage(),
                ]);
                // Continue even if subscription fails - it's not critical for linking
            }

            // 6) Save to database - use phone_id as unique key so each phone gets its own row
            // This allows users to link multiple phone numbers
            $whatsappUser = WhatsappUser::updateOrCreate(
                [
                    'user_id' => $userId,
                    'phone_id' => $phoneId,  // Each phone number is a separate row
                ],
                [
                    'number' => $displayPhoneNumber,
                    'name' => $verifiedName,
                    'status' => 'active',
                    'request_status' => 'active',
                    'token' => $finalToken,
                    'access_token' => $finalToken,
                    'token_expires_at' => $expiresAt,
                    'business_id' => $wabaId, // WABA ID is the business account ID
                    'waba_id' => $wabaId,
                ]
            );

            // Keep Communication/AI wa_numbers in sync (used by /api/v1/whatsapp/* and AI bot).
            $waNumber = $this->syncWaNumber->syncQuietly($whatsappUser);

            Log::info('MetaOAuthController.callback WhatsApp linked successfully', [
                'user_id' => $userId,
                'waba_id' => $wabaId,
                'phone_id' => $phoneId,
                'display_phone_number' => $displayPhoneNumber,
                'is_new_phone' => !in_array($phoneId, $existingPhoneIds),
                'wa_number_id' => $waNumber?->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp Business account linked successfully.',
                'data' => [
                    'whatsapp_user_id' => $whatsappUser->id,
                    'wa_number_id' => $waNumber?->id,
                    'waba_id' => $wabaId,
                    'phone_number_id' => $phoneId,
                    'display_phone_number' => $displayPhoneNumber,
                    'verified_name' => $verifiedName,
                    'token_expires_at' => optional($expiresAt)->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MetaOAuthController.callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete Meta Embedded Signup.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


