<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappUserToWaNumberService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappUser;
use App\Services\MetaGraphService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $owner = $user->tenantOwner();

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

        if ($mode === 'new' && $owner->whatsapp_usage >= $owner->whatsapp_quota) {
            $this->logMetaEvent('warning', 'MetaOAuthController.redirect blocked by quota', $this->metaLogContext(
                $request,
                $owner,
                $user,
                $user->isEmployee() ? (int) $user->id : null,
                $mode
            ));

            return response()->json([
                'success' => false,
                'message' => 'لقد وصلت للحد الأقصى لعدد الأرقام المسموح بها. يرجى شراء إضافة لزيادة الحد.',
            ], 422);
        }

        // Keep user_id for callbacks already integrated with this payload.
        $statePayload = [
            'user_id' => $user->id,
            'actor_user_id' => $user->id,
            'tenant_owner_id' => $owner->id,
            'mode' => $mode,
            'issued_at' => now()->timestamp,
        ];

        $state = Crypt::encryptString(json_encode($statePayload));

        $this->logMetaEvent('info', 'MetaOAuthController.redirect started', $this->metaLogContext(
            $request,
            $owner,
            $user,
            $user->isEmployee() ? (int) $user->id : null,
            $mode
        ));

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
        $attemptId = (string) Str::uuid();
        $error = $request->query('error');
        if ($error) {
            $this->logMetaEvent('warning', 'MetaOAuthController.callback received error from Meta', array_merge(
                $this->metaStateLogContext($request),
                [
                'attempt_id' => $attemptId,
                'error' => $error,
                'error_code' => $request->query('error_code'),
                'error_reason' => $request->query('error_reason'),
                'error_description' => $request->query('error_description'),
                'action' => $request->query('action'),
                ]
            ));

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
            $this->logMetaEvent('warning', 'MetaOAuthController.callback missing code or state', [
                'has_code' => (bool) $code,
                'has_state' => (bool) $state,
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Missing authorization code or state.',
            ], 400);
        }

        // Decrypt state to recover user context
        try {
            $decoded = json_decode(Crypt::decryptString($state), true);
        } catch (DecryptException $e) {
            $this->logMetaEvent('warning', 'MetaOAuthController.callback invalid state', [
                'reason' => 'decrypt_failed',
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid state parameter.',
            ], 400);
        }

        if (!is_array($decoded) || empty($decoded['user_id'])) {
            $this->logMetaEvent('warning', 'MetaOAuthController.callback invalid state', [
                'reason' => 'invalid_payload',
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid state payload.',
            ], 400);
        }

        $actorUserId = (int) ($decoded['actor_user_id'] ?? $decoded['user_id']);
        $actor = User::find($actorUserId);

        if (!$actor || ($actor->isEmployee() && (!$actor->active || !$actor->tenant_id))) {
            $this->logMetaEvent('warning', 'MetaOAuthController.callback actor unavailable', [
                'actor_user_id' => $actorUserId,
                'tenant_owner_id' => $decoded['tenant_owner_id'] ?? null,
                'mode' => $decoded['mode'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'The user who started this signup is no longer available.',
            ], 403);
        }

        $owner = isset($decoded['tenant_owner_id'])
            ? User::find((int) $decoded['tenant_owner_id'])
            : $actor->tenantOwner();

        if (!$owner || $owner->isEmployee() || $actor->tenantOwnerId() !== (int) $owner->id) {
            $this->logMetaEvent('warning', 'MetaOAuthController.callback invalid tenant context', [
                'actor_user_id' => $actorUserId,
                'tenant_owner_id' => $decoded['tenant_owner_id'] ?? null,
                'actor_tenant_owner_id' => $actor->tenantOwnerId(),
                'mode' => $decoded['mode'] ?? null,
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid tenant context in state payload.',
            ], 400);
        }

        $ownerId = (int) $owner->id;
        $employeeId = $actor->isEmployee() ? (int) $actor->id : null;
        $callbackLogContext = $this->metaLogContext($request, $owner, $actor, $employeeId, $decoded['mode'] ?? null);
        $callbackLogContext['attempt_id'] = $attemptId;

        try {
            // 1) Exchange code for short-lived token
            $tokenResponse = $this->metaGraph->exchangeCodeForToken($code, $callbackLogContext);
            $shortLivedToken = $tokenResponse['access_token'] ?? null;
            $expiresIn = $tokenResponse['expires_in'] ?? null;

            if (!$shortLivedToken) {
                throw new \RuntimeException('No access_token in Meta response.');
            }

            // 2) Upgrade to long-lived token
            $finalToken = $shortLivedToken;
            $expiresAt = null;

            try {
                $longLived = $this->metaGraph->exchangeForLongLivedToken($shortLivedToken, $callbackLogContext);
                if (!empty($longLived['access_token'])) {
                    $finalToken = $longLived['access_token'];
                    $longExpiresIn = $longLived['expires_in'] ?? null;
                    if ($longExpiresIn) {
                        $expiresAt = Carbon::now()->addSeconds((int) $longExpiresIn);
                    }
                }
            } catch (\Throwable $e) {
                $this->logMetaEvent('warning', 'MetaOAuthController.callback long-lived token exchange failed', array_merge(
                    $callbackLogContext,
                    [
                    'error' => $e->getMessage(),
                    ]
                ));
                if ($expiresIn) {
                    $expiresAt = Carbon::now()->addSeconds((int) $expiresIn);
                }
            }

            // 3) Debug token to get WABA ID from granular scopes
            $debugTokenResponse = $this->metaGraph->debugToken($finalToken, $callbackLogContext);
            $wabaId = $this->metaGraph->extractWabaIdFromDebugToken($debugTokenResponse);

            if (!$wabaId) {
                $this->logMetaEvent('warning', 'MetaOAuthController.callback no WABA found', $callbackLogContext);

                return response()->json([
                    'success' => false,
                    'message' => 'No WhatsApp Business Account found in token scopes.',
                ], 400);
            }

            $this->logMetaEvent('info', 'MetaOAuthController.callback WABA ID extracted', array_merge(
                $callbackLogContext,
                [
                'waba_id' => $wabaId,
                ]
            ));

            // 4) Get phone numbers for that WABA
            $phonesResponse = $this->metaGraph->listPhoneNumbers($finalToken, $wabaId, $callbackLogContext);
            $phones = $phonesResponse['data'] ?? [];

            if (empty($phones)) {
                $this->logMetaEvent('warning', 'MetaOAuthController.callback no phone numbers found', array_merge(
                    $callbackLogContext,
                    ['waba_id' => $wabaId]
                ));

                return response()->json([
                    'success' => false,
                    'message' => 'No phone number found for WhatsApp Business Account.',
                ], 400);
            }

            // Get existing phone_ids for this user to find the newly added one
            $existingPhoneIds = WhatsappUser::where('user_id', $ownerId)
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
                $this->logMetaEvent('warning', 'MetaOAuthController.callback invalid phone data', array_merge(
                    $callbackLogContext,
                    ['waba_id' => $wabaId]
                ));

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid phone number data from Meta.',
                ], 400);
            }

            // Serialize links per tenant so concurrent callbacks cannot exceed the shared quota.
            $linkResult = DB::transaction(function () use (
                $ownerId,
                $employeeId,
                $phoneId,
                $displayPhoneNumber,
                $verifiedName,
                $finalToken,
                $expiresAt,
                $wabaId
            ) {
                $lockedOwner = User::query()->whereKey($ownerId)->lockForUpdate()->firstOrFail();
                $whatsappUser = WhatsappUser::query()
                    ->where('user_id', $ownerId)
                    ->where('phone_id', $phoneId)
                    ->first();

                if ($employeeId !== null) {
                    if ($whatsappUser && $whatsappUser->employee_id && (int) $whatsappUser->employee_id !== $employeeId) {
                        return [
                            'error' => 'This WhatsApp number is already assigned to another employee.',
                            'status' => 422,
                            'reason' => 'assigned_to_another_employee',
                            'assigned_employee_id' => (int) $whatsappUser->employee_id,
                        ];
                    }

                    $employeeNumber = WhatsappUser::query()
                        ->where('user_id', $ownerId)
                        ->where('employee_id', $employeeId)
                        ->first();

                    if ($employeeNumber && (!$whatsappUser || $employeeNumber->id !== $whatsappUser->id)) {
                        return [
                            'error' => 'This employee already has a WhatsApp number assigned.',
                            'status' => 422,
                            'reason' => 'employee_already_has_number',
                            'existing_whatsapp_user_id' => (int) $employeeNumber->id,
                        ];
                    }
                }

                $usesNewSlot = !$whatsappUser || $whatsappUser->status !== 'active';
                $usage = WhatsappUser::query()
                    ->where('user_id', $ownerId)
                    ->where('status', 'active')
                    ->count();

                if ($usesNewSlot && $usage >= $lockedOwner->whatsapp_quota) {
                    return [
                        'error' => 'لقد وصلت للحد الأقصى لعدد الأرقام المسموح بها. يرجى شراء إضافة لزيادة الحد.',
                        'status' => 422,
                        'reason' => 'quota_exceeded',
                        'quota' => $lockedOwner->whatsapp_quota,
                        'usage' => $usage,
                    ];
                }

                $whatsappUser ??= new WhatsappUser([
                    'user_id' => $ownerId,
                    'phone_id' => $phoneId,
                ]);

                $whatsappUser->fill([
                    'number' => $displayPhoneNumber,
                    'name' => $verifiedName,
                    'status' => 'active',
                    'request_status' => 'active',
                    'token' => $finalToken,
                    'access_token' => $finalToken,
                    'token_expires_at' => $expiresAt,
                    'business_id' => $wabaId, // WABA ID is the business account ID
                    'waba_id' => $wabaId,
                ]);

                if ($employeeId !== null) {
                    $whatsappUser->employee_id = $employeeId;
                }

                $whatsappUser->save();

                return [
                    'whatsapp_user' => $whatsappUser,
                    'is_new_phone' => $whatsappUser->wasRecentlyCreated,
                ];
            });

            if (isset($linkResult['error'])) {
                $this->logMetaEvent('warning', 'MetaOAuthController.callback link blocked', array_merge(
                    $callbackLogContext,
                    [
                        'reason' => $linkResult['reason'] ?? 'unknown',
                        'status' => $linkResult['status'],
                        'quota' => $linkResult['quota'] ?? null,
                        'usage' => $linkResult['usage'] ?? null,
                        'phone_id' => $phoneId,
                        'waba_id' => $wabaId,
                        'assigned_employee_id' => $linkResult['assigned_employee_id'] ?? null,
                        'existing_whatsapp_user_id' => $linkResult['existing_whatsapp_user_id'] ?? null,
                    ]
                ));

                return response()->json([
                    'success' => false,
                    'message' => $linkResult['error'],
                ], $linkResult['status']);
            }

            /** @var WhatsappUser $whatsappUser */
            $whatsappUser = $linkResult['whatsapp_user'];

            // Subscribe only after the tenant and quota checks have accepted the number.
            try {
                $this->metaGraph->subscribeAppToWaba($finalToken, $wabaId, $callbackLogContext);
            } catch (\Throwable $e) {
                $this->logMetaEvent('warning', 'MetaOAuthController.callback WABA subscription failed (non-fatal)', array_merge(
                    $callbackLogContext,
                    [
                    'waba_id' => $wabaId,
                    'error' => $e->getMessage(),
                    ]
                ));
            }

            // Keep Communication/AI wa_numbers in sync (used by /api/v1/whatsapp/* and AI bot).
            $waNumber = $this->syncWaNumber->syncQuietly($whatsappUser);

            $this->logMetaEvent('info', 'MetaOAuthController.callback WhatsApp linked successfully', array_merge(
                $callbackLogContext,
                [
                'tenant_owner_id' => $ownerId,
                'actor_user_id' => $actorUserId,
                'employee_id' => $employeeId,
                'actor_email' => $actor->email,
                'whatsapp_user_id' => $whatsappUser->id,
                'waba_id' => $wabaId,
                'phone_id' => $phoneId,
                'display_phone_number' => $displayPhoneNumber,
                'is_new_phone' => $linkResult['is_new_phone'],
                'wa_number_id' => $waNumber?->id,
                ]
            ));

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
            $this->logMetaEvent('error', 'MetaOAuthController.callback failed', array_merge(
                $callbackLogContext ?? $this->metaStateLogContext($request),
                [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                ]
            ));

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete Meta Embedded Signup.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function metaStateLogContext(Request $request): array
    {
        $state = $request->query('state');

        if (!$state) {
            return [
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ];
        }

        try {
            $decoded = json_decode(Crypt::decryptString((string) $state), true);
        } catch (\Throwable $e) {
            return [
                'state_status' => 'decrypt_failed',
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ];
        }

        if (!is_array($decoded)) {
            return [
                'state_status' => 'invalid_payload',
                'ip' => $request->ip(),
                'user_agent' => $this->safeUserAgent($request),
            ];
        }

        $actor = !empty($decoded['actor_user_id']) || !empty($decoded['user_id'])
            ? User::find((int) ($decoded['actor_user_id'] ?? $decoded['user_id']))
            : null;
        $owner = !empty($decoded['tenant_owner_id'])
            ? User::find((int) $decoded['tenant_owner_id'])
            : ($actor ? $actor->tenantOwner() : null);

        return $this->metaLogContext(
            $request,
            $owner,
            $actor,
            $actor && $actor->isEmployee() ? (int) $actor->id : null,
            $decoded['mode'] ?? null
        );
    }

    private function metaLogContext(Request $request, ?User $owner, ?User $actor, ?int $employeeId, ?string $mode): array
    {
        $quota = $owner?->whatsapp_quota;
        $usage = $owner?->whatsapp_usage;

        return [
            'tenant_owner_id' => $owner?->id,
            'actor_user_id' => $actor?->id,
            'employee_id' => $employeeId,
            'actor_email' => $actor?->email,
            'mode' => $mode,
            'quota' => $quota,
            'usage' => $usage,
            'remaining' => $quota !== null && $usage !== null ? max(0, (int) $quota - (int) $usage) : null,
            'ip' => $request->ip(),
            'user_agent' => $this->safeUserAgent($request),
        ];
    }

    private function safeUserAgent(Request $request): ?string
    {
        $userAgent = $request->userAgent();

        return $userAgent ? mb_substr($userAgent, 0, 500) : null;
    }

    private function logMetaEvent(string $level, string $message, array $context = []): void
    {
        try {
            Log::log($level, $message, $context);
        } catch (\Throwable $e) {
            // Logging must never interrupt Meta signup.
        }
    }
}
