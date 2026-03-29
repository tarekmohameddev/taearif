<?php

namespace App\Http\Controllers\Api;


// use Str;
use Carbon\Carbon;
use App\Models\Api;
use App\Models\User;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\Language;
use App\Models\User\SEO;
use App\Http\Requests\Api\Auth\LoginApiRequest;
use App\Http\Requests\Api\Auth\LogoutApiRequest;
use App\Http\Requests\Api\Auth\ReadMessageRequest;
use App\Http\Requests\Api\Auth\RegisterApiRequest;
use App\Http\Requests\Api\Auth\AuthVerifyResetCodeRequest;
use App\Rules\Recaptcha;
use App\Models\User\Blog;
use App\Models\User\Menu;
use App\Models\Membership;
use App\Models\User\Member;
use App\Models\User\Social;
use App\Models\OtpVerification;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\OfflineGateway;
use App\Models\User\Portfolio;
use App\Models\User\HeroSlider;
use App\Http\Helpers\MegaMailer;
use App\Models\User\HomeSection;
use App\Models\User\UserService;
use App\Models\User\WorkProcess;
use App\Models\User\BasicSetting;
use App\Models\User\BlogCategory;
use App\Models\User\HomePageText;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Api\ApiMenuSetting;
use App\Services\TempTokenService;
use Illuminate\Support\Facades\DB;
use App\Models\User\UserPermission;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiDomainSetting;
use App\Models\User\UserShopSetting;
use App\Models\User\UserTestimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Models\User\PortfolioCategory;
use App\Models\User\UserEmailTemplate;
use App\Models\User\UserPaymentGeteway;
use App\Models\EmployeeAddon;
use App\Models\WhatsappAddon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\UserPermissionHelper;
use App\Services\Rbac\BootstrapTenantRbac;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Controllers\Api\OnboardingController;
use App\Models\User\RealestateManagement\Category;


class AuthController extends Controller
{

    private function guardAccountType(?string $expected, User $user): bool
    {
        if (!$expected) return true; // no restriction
        return $user->account_type === $expected;
    }

    /**
     * Use tenant for membership ownership if employee.
     */
    private function ownerUser(User $user): User
    {
        return $user->isEmployee() && $user->tenant ? $user->tenant : $user;
    }

    public function verifyResetCode(AuthVerifyResetCodeRequest $request)
    {
        $validated = $request->validated();

        $user = User::where('email', $validated['identifier'])
            ->orWhere('phone', $validated['identifier'])
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $log = PasswordResetLog::where('user_id', $user->id)
            ->where('code', $validated['code'])
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        // update password
        $user->update(['password' => bcrypt($validated['new_password'])]);

        // mark code as used
        $log->update(['used' => true]);

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }


    /**
     * Check if the request expects a JSON response
     */
    private function shouldReturnJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->header('Accept') === 'application/json';
    }

    public function redirect()
    {
        try {
            $url = Socialite::driver('google')
                ->stateless() // Use stateless for API
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => 'success',
                'url' => $url,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Google Redirect Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'code' => 'GOOGLE_REDIRECT_FAILED',
                'message' => __('Unable to generate Google auth URL.'),
            ], 500);
        }
    }

    public function callback(Request $request)
    {
        $wantsJson = $this->shouldReturnJson($request);

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Validate Google user data
            if (!$googleUser || !$googleUser->email) {
                $error = 'Invalid Google user data received';
                Log::error('Google Callback Error: ' . $error, [
                    'google_user' => $googleUser ? ['id' => $googleUser->id] : null,
                ]);

                if ($wantsJson) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'INVALID_GOOGLE_DATA',
                        'message' => 'Invalid Google authentication data',
                    ], 400);
                }

                return redirect()->away("https://api.taearif.com/oauth/login?error=invalid_google_data");
            }

            // Find user by email or google_id
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();

            // New user - redirect to extra info page
            if (!$user) {
                $payload = [
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ];

                $tempToken = TempTokenService::generate($payload);

                if ($wantsJson) {
                    return response()->json([
                        'status' => 'success',
                        'code' => 'REGISTRATION_REQUIRED',
                        'message' => 'Additional information required for registration',
                        'data' => [
                            'temp_token' => $tempToken,
                            'redirect_url' => "https://api.taearif.com/oauth/social/extra-info?temp_token={$tempToken}",
                        ],
                    ], 200);
                }

                return redirect()->away("https://api.taearif.com/oauth/social/extra-info?temp_token={$tempToken}");
            }

            // Link Google account to existing user
            if ($user->email === $googleUser->email && !$user->google_id) {
                $user->update(['google_id' => $googleUser->id]);
                $user->save();
            }

            // Check if account is banned
            if ($user->status == 0) {
                if ($wantsJson) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'ACCOUNT_BANNED',
                        'message' => 'Your account has been banned',
                    ], 403);
                }

                return redirect()->away('https://api.taearif.com/oauth/login?error=account_banned');
            }

            // Authenticate user
            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            if ($wantsJson) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Authentication successful',
                    'data' => [
                        'token' => $token,
                        'user' => [
                            'id' => $user->id,
                            'email' => $user->email,
                            'name' => $user->name,
                        ],
                    ],
                ], 200);
            }

            return redirect()->away("https://api.taearif.com/oauth/token/success?token={$token}");

        } catch (\Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'request_url' => $request->fullUrl(),
            ]);

            if ($wantsJson) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'GOOGLE_AUTH_FAILED',
                    'message' => 'Google authentication failed',
                    'errors' => config('app.debug') ? [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : null,
                ], 500);
            }

            return redirect()->away("https://api.taearif.com/oauth/login?error=google_auth_failed");
        }
    }

    // Helper method to generate unique username
    private function generateUniqueUsername($name)
    {
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        return $username;
    }

    public function register(RegisterApiRequest $request)
    {
        try {
            $validated = $request->validated();

            /**
             * ============================================================
             * EMPLOYEE CREATION BRANCH (no website, no membership, no subdomain)
             * Trigger when: account_type=employee AND user_id is provided
             * ============================================================
             */
            if (request()->input('account_type') === 'employee' && request()->filled('user_id')) {
                // Ensure the parent is a tenant (not another employee)
                $tenant = User::findOrFail($validated['user_id']);
                if (($tenant->account_type ?? 'tenant') !== 'tenant') {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Provided user_id must be a tenant account.',
                    ], 422);
                }

                // Check employee quota
                if ($tenant->employee_usage >= $tenant->employee_quota) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'لقد وصلت للحد الأقصى لعدد الموظفين المسموح بهم. يرجى شراء إضافة لزيادة الحد.',
                    ], 422);
                }

                // Create the employee account (NO website materials, NO memberships)
                $employee = User::create([
                    'first_name'   => request()->input('first_name', 'employee'),
                    'last_name'    => request()->input('last_name', 'employee'),
                    'company_name' => $tenant->company_name, // inherit if desired
                    'email'        => $validated['email'],
                    'username'     => $validated['username'],
                    'password'     => bcrypt($validated['password']),
                    'status'       => 1,
                    'active'       => true,
                    'tenant_id'    => $tenant->id,
                    'account_type' => 'employee',
                    'onboarding_completed' => true, // Employees skip onboarding
                    // Do NOT set website-related fields; do NOT create languages/menus/etc.
                ]);

                // Optional role/permission assignment (Spatie compatible)
                if (request()->filled('roles') && is_array(request()->input('roles')) && method_exists($employee, 'syncRoles')) {
                    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
                    $employee->syncRoles(request()->input('roles'));
                }
                if (request()->filled('permissions') && is_array(request()->input('permissions')) && method_exists($employee, 'syncPermissions')) {
                    $employee->syncPermissions(request()->input('permissions'));
                }

                // Consume pre-registration OTP verification proof (if provided)
                $verifiedToken = request()->input('verified_token');
                if (!empty($verifiedToken)) {
                    $otpRecord = OtpVerification::query()
                        ->where('verified_token', $verifiedToken)
                        ->where('verified_token_expires_at', '>=', now())
                        ->where('identifier', $validated['phone'] ?? null)
                        ->whereNull('user_id')
                        ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                        ->first();

                    if (!$otpRecord) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid or expired verified_token',
                        ], 422);
                    }

                    $employee->update(['phone_verified_at' => now()]);
                    $otpRecord->update([
                        'user_id' => $employee->id,
                        'verified_token' => null,
                        'verified_token_expires_at' => null,
                    ]);
                }

                // Never auto-login employee here; the employee will log in via the same /login route.
                return response()->json([
                    'status'   => 'success',
                    'message'  => 'Employee created under tenant.',
                    'employee' => $employee,
                ], 201);
            }

            /**
             * ============================================================
             * TENANT (USER) REGISTRATION (existing behavior preserved)
             * ============================================================
             */
            $tempToken = request()->input('temp_token');

            if ($tempToken) {
                // Google OAuth temp_token registration
                $tokenData = \App\Services\TempTokenService::decrypt($tempToken);

                if (!$tokenData) {
                    return response()->json([
                        'status' => 'error',
                        'error'  => 'invalid_or_expired_temp_token'
                    ], 400);
                }

                if (User::where('email', $tokenData['email'])->where('google_id', $tokenData['google_id'])->exists()) {
                    return response()->json([
                        'status' => 'error',
                        'error'  => 'already_registered'
                    ], 409);
                }

                request()->merge([
                    'email' => $tokenData['email'],
                    'google_id' => $tokenData['google_id'],
                    'password' => Str::random(32),
                ]);
            } else {
            }

            // Map ?code query param to referral_code if provided
            if (!request()->filled('referral_code') && request()->filled('code')) {
                request()->merge(['referral_code' => request()->input('code')]);
            }

            // Referral code (optional)
            $referrer = null;
            if (request()->filled('referral_code')) {
                $referrer = \App\Models\User::where('referral_code', request()->input('referral_code'))->first();
                if (!$referrer) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Invalid referral code.'
                    ], 400);
                }
            }

            // Get package details for trial registration
            $package = Package::findOrFail(26);

            // Static trial registration values
            request()->merge([
                'status'         => 1,
                'mode'           => 'online',
                'receipt_name'   => null,
                'price'          => $package->price,
                'first_name'     => request()->input('first_name', 'User'),
                'last_name'      => request()->input('last_name', ''),
                'company_name'   => 'N/A',
                'country'        => 'N/A',
                'is_receipt'     => 0,
                'address'        => 'N/A',
                'city'           => 'N/A',
                'district'       => 'N/A',
                'package_type'   => 'trial',
                'package_id'     => 26,
                'trial_days'     => $package->trial_days,
                'start_date'     => now()->toDateString(),
                'expire_date'    => now()->addDays($package->trial_days)->toDateString(),
                'payment_method' => $tempToken ? 'google' : '-',
                'referral_code'  => strtoupper(Str::random(8)),
                'referred_by'    => $referrer?->id,
                'account_type'   => 'tenant',
                'tenant_id'      => null,
            ]);

            // Coupon logic
            $coupon = \App\Models\Coupon::where('code', Session::get('coupon'))->first();
            if ($coupon && $coupon->maximum_uses_limit != 999999 && $coupon->total_uses >= $coupon->maximum_uses_limit) {
                Session::forget('coupon');
                return response()->json([
                    'status'  => 'error',
                    'message' => __('This coupon reached maximum limit')
                ], 400);
            }

            // Language config
            $currentLang = session()->has('lang')
                ? \App\Models\Language::where('code', session()->get('lang'))->first()
                : \App\Models\Language::where('is_default', 1)->first();
            $be = $currentLang->basic_extended;

            // Membership + website creation (existing flow)
            $transaction_id      = \App\Http\Helpers\UserPermissionHelper::uniqidReal(8);
            $transaction_details = request()->input('package_type') === 'trial' ? 'Trial' : 'Free';
            $price               = $package->price;

            // Define welcome message before create_website call
            $trialPeriod = $package->trial_days == 1 ? 'يوم واحد' :
                           ($package->trial_days == 7 ? '7 أيام' :
                           ($package->trial_days == 30 ? 'شهر' :
                           $package->trial_days . ' أيام'));
            $welcome_message = 'شكراً على التسجيل في منصة تعاريف انت الآن على الباقة المميزة لمدة ' . $trialPeriod;

            $user = $this->create_website(
                request()->all(),
                $transaction_id,
                $transaction_details,
                $price,
                $be,
                request()->input('password'),
                $welcome_message
            );

            // Consume pre-registration OTP verification proof (if provided)
            $verifiedToken = request()->input('verified_token');
            if (!empty($verifiedToken)) {
                $otpRecord = OtpVerification::query()
                    ->where('verified_token', $verifiedToken)
                    ->where('verified_token_expires_at', '>=', now())
                    ->where('identifier', $validated['phone'] ?? null)
                    ->whereNull('user_id')
                    ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                    ->first();

                if ($otpRecord) {
                    $user->update(['phone_verified_at' => now()]);
                    $otpRecord->update([
                        'user_id' => $user->id,
                        'verified_token' => null,
                        'verified_token_expires_at' => null,
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid or expired verified_token',
                    ], 422);
                }
            }

            // Create default roles & permissions INSIDE this tenant
            DB::afterCommit(fn() => app(\App\Services\TenantRbacBootstrapper::class)->run($user->id));


            // Log in tenant
            Auth::login($user);

            // Seed default tenant website pages and components (FIRST TIME - before onboarding)
            app(\App\Services\TenantWebsiteSeeder::class)->seedDefaultWebsite($user);

            // Onboarding + default categories
            app(\App\Services\OnboardingService::class)->applyDefaultsFor($user);

            // Re-seed tenant website pages with updated onboarding settings (SECOND TIME - after onboarding)
           // app(\App\Services\TenantWebsiteSeeder::class)->reseedWebsite($user);

            $categories = \DB::table('api_user_categories')->get();
            foreach ($categories as $category) {
                \DB::table('api_user_category_settings')->insert([
                    'user_id'     => $user->id,
                    'category_id' => $category->id,
                    'is_active'   => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            // Token + membership info
            $token    = $user->createToken('auth_token')->plainTextToken;
            $lastMemb = $user->memberships()->latest()->first();
            $activation = \Carbon\Carbon::parse($lastMemb->start_date);
            $expire     = \Carbon\Carbon::parse($lastMemb->expire_date);

            // Send WhatsApp welcome message if enabled
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                $bs = \App\Models\BasicSetting::first();

                if ($bs && $bs->welcome_message_enabled && !empty($bs->welcome_message_text) && !empty($user->phone)) {
                    // Add delay if configured
                    $delay = $bs->welcome_message_delay ?? 5;

                    // Schedule the welcome message with delay
                    \App\Jobs\SendWelcomeMessageJob::dispatch($user, $bs->welcome_message_text)
                        ->delay(now()->addSeconds($delay));
                }
            } catch (\Exception $e) {
                // Log error but don't fail registration
                \Log::error('WhatsApp welcome message failed', [
                    'user_id' => $user->id,
                    'phone' => $user->phone ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }

            $user['onboarding_completed'] = false;
            return response()->json([
                'status' => 'success',
                'user'   => $user,
                'token'  => $token,
                'membership' => [
                    'start_date'  => $activation->toDateString(),
                    'expire_date' => $expire->toDateString(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //sendWhatsAppMessage
    public function sendWhatsAppMessage($phone, $message)
    {
        try {
            $url = 'https://whatsapp-evolution-api.3dxvu8.easypanel.host/message/sendText/ddd';
            $apiKey = '286540DD68F4-4EE2-AAE1-25A7177E44BD';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $apiKey,
            ])->post($url, [
                'number' => $phone,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return true;
            } else {
                \Log::error('WhatsApp API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Exception while sending WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }

    public function login(LoginApiRequest $request)
    {
        $validated = $request->validated();

        if (!empty($validated['email'])) {
            $credentials = [
                'email' => $validated['email'],
                'password' => $validated['password'],
            ];
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
        } else {
            $rawPhone = trim((string) $validated['phone']);
            $phoneCandidates = PhoneNormalizer::loginLookupValues($rawPhone);

            /** @var \App\Models\User|null $found */
            $found = $phoneCandidates === []
                ? null
                : User::query()->whereIn('phone', $phoneCandidates)->first();

            if (!$found || !Hash::check($validated['password'], $found->password)) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            Auth::login($found);
        }

        // Get authenticated user
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // block banned/inactive accounts (works for both tenant & employee)
        if (!$user->active || $user->status == 0) {
            Auth::logout();
            return response()->json(['message' => 'Account inactive or banned'], 403);
        }

        // optional: restrict employee if tenant is banned/inactive
        if ($user->account_type === 'employee' && $user->tenant_id) {
            $tenant = User::find($user->tenant_id);
            if (!$tenant || !$tenant->active || $tenant->status == 0) {
                Auth::logout();
                return response()->json(['message' => 'Tenant is inactive; employee login disabled'], 403);
            }
        }

        $user->forceFill(['last_login_at' => now()])->save();

        // Create token for API authentication
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(LogoutApiRequest $request)
    {
        if (!auth()->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            // Always revoke the Bearer token (handles TransientToken case when Sanctum auth's via another guard)
            $bearer = $request->bearerToken();
            if ($bearer !== null && $bearer !== '') {
                $accessToken = PersonalAccessToken::findToken($bearer);
                if ($accessToken) {
                    $accessToken->delete();
                }
            }
        } catch (\Throwable $e) {
            $bearer = $request->bearerToken();
            if ($bearer !== null && $bearer !== '') {
                $accessToken = PersonalAccessToken::findToken($bearer);
                if ($accessToken) {
                    $accessToken->delete();
                }
            }
            Log::warning('Logout token revoke (fallback)', ['error' => $e->getMessage()]);
        }
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function getUserProfile()
    {
        try {
            // Get authenticated user from API token
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Check if performance optimizations are enabled (default to true for better performance)
            $useOptimizations = config('performance.enable_api_performance_optimizations', true);
            /**
             * IMPORTANT PERF NOTE:
             * We want cache hits to be as cheap as possible.
             * So we compute ownerId WITHOUT loading any relationships first,
             * then short-circuit on cache hit before any extra DB work.
             */
            $ownerId = null;
            if (method_exists($user, 'tenantOwnerId')) {
                $ownerId = $user->tenantOwnerId();
            } elseif (method_exists($user, 'isEmployee') && $user->isEmployee() && !empty($user->tenant_id)) {
                $ownerId = (int) $user->tenant_id;
            } else {
                $ownerId = (int) $user->id;
            }

            $cacheKey = "user:profile:{$user->id}:{$ownerId}";
            $cacheTtl = 3600; // 60 minutes

            if ($useOptimizations) {
                // Try to get from cache first
                $cachedData = Cache::get($cacheKey);
                if ($cachedData !== null) {
                    return response()->json([
                        'status' => 'success',
                        'data' => $cachedData,
                    ], 200);
                }
            }

            // Resolve tenant owner (tenant for tenant; tenant for employee)
            // Eager load tenant relationship if user is an employee to avoid N+1 query
            if ($user->isEmployee() && !$user->relationLoaded('tenant')) {
                $user->load('tenant');
            }
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

            // Get current date for comparing with membership expiration
            $currentDate = now();

            if ($useOptimizations) {
                // OPTIMIZATION: Use direct queries with limit(1) instead of eager loading all records
                // This is much faster when we only need the latest/active record

                // Get latest membership with package in a single optimized query
                $membership = Membership::where('user_id', $owner->id)
                    ->select([
                        'id', 'user_id', 'package_id', 'package_price', 'discount',
                        'coupon_code', 'price', 'currency', 'currency_symbol',
                        'payment_method', 'transaction_id', 'status', 'is_trial',
                        'trial_days', 'start_date', 'expire_date'
                    ])
                    ->orderBy('id', 'desc')
                    ->with(['package' => function ($pkgQuery) {
                        $pkgQuery->select([
                            'id', 'title', 'video_size_limit', 'file_size_limit',
                            'number_of_vcards', 'trial_days', 'features',
                            'project_limit_number', 'real_estate_limit_number',
                            'whatsapp_numbers_limit', 'employees_limit'
                        ]);
                    }])
                    ->limit(1)
                    ->first();

                // Get active domain with limit(1) - only fetch what we need
                $domain = ApiDomainSetting::where('user_id', $owner->id)
                    ->where('status', 'active')
                    ->select(['id', 'user_id', 'custom_name', 'status', 'primary', 'ssl'])
                    ->limit(1)
                    ->first();

                // Get company name with limit(1)
                $basicSetting = BasicSetting::where('user_id', $owner->id)
                    ->select(['id', 'user_id', 'company_name'])
                    ->limit(1)
                    ->first();
                $companyName = $basicSetting?->company_name;

                // Eager load employee counts to avoid N+1 queries
                // Use withCount to get counts in a single query instead of two separate count() calls
                $owner->loadCount([
                    'employees as total_employees_count',
                    'employees as active_employees_count' => function ($query) {
                        $query->where('active', true);
                    }
                ]);
            } else {
                // Original code path without optimizations
                // Get owner's latest membership from the membership table
                $membership = Membership::where('user_id', $owner->id)
                    ->orderBy('id', 'desc')
                    ->first();

                $domain = ApiDomainSetting::where('user_id', $owner->id)->where('status', 'active')->first([
                    "custom_name",
                    "status",
                    "primary",
                    "ssl",
                ]);

                // Get company_name from BasicSetting (owner)
                $basicSetting = BasicSetting::where('user_id', $owner->id)->first(['company_name']);
                $companyName = $basicSetting ? $basicSetting->company_name : null;
            }

              $membershipDetails = null;
              $isFreePlan = true;
              $isExpired = true;

            if ($membership) {
                // Determine if membership is expired
                $isExpired = $currentDate->gt($membership->expire_date);

                // Determine if it's a free plan (price = 0)
                $isFreePlan = (float)$membership->price <= 0;

                // Format membership details
                $membershipDetails = [
                    'id' => $membership->id,
                    'package_id' => $membership->package_id,
                    'package_price' => $membership->package_price,
                    'price' => $membership->price,
                    'discount' => $membership->discount,
                    'coupon_code' => $membership->coupon_code,
                    'currency' => $membership->currency,
                    'currency_symbol' => $membership->currency_symbol,
                    'payment_method' => $membership->payment_method,
                    'transaction_id' => $membership->transaction_id,
                    'status' => $membership->status,
                    'is_trial' => $membership->is_trial,
                    'trial_days' => $membership->trial_days,
                    'start_date' => $membership->start_date,
                    'expire_date' => $membership->expire_date,
                    'is_expired' => $isExpired,
                    'days_remaining' => $isExpired ? 0 : $currentDate->diffInDays($membership->expire_date),
                    'is_free_plan' => $isFreePlan
                ];

                // Get package details if needed (already loaded via eager loading in direct query if optimizations enabled)
                if ($membership->package_id) {
                    if ($useOptimizations && $membership->relationLoaded('package') && $membership->package) {
                        // Package already loaded via eager loading in the direct query
                        $package = $membership->package;
                    } else {
                        // Fallback: load package if not eager loaded
                        $package = Package::find($membership->package_id);
                    }

                    if ($package) {
                        $membershipDetails['package'] = [
                            'title' => $package->title,
                            'video_size_limit' => $package->video_size_limit,
                            'file_size_limit' => $package->file_size_limit,
                            'number_of_vcards' => $package->number_of_vcards,
                            'trial_days' => $package->trial_days,
                            'features' => json_decode($package->features, true),
                            'project_limit_number' => $package->project_limit_number,
                            'real_estate_limit_number' => $package->real_estate_limit_number,
                            'whatsapp_numbers_limit' => $package->whatsapp_numbers_limit,
                            'employees_limit' => $package->employees_limit,
                        ];
                    }
                }
              }

              // Avoid duplicate membership lookups by computing quotas here
              $baseWhatsappLimit = isset($membershipDetails['package'])
                  ? (int) $membershipDetails['package']['whatsapp_numbers_limit']
                  : 0;
              $baseEmployeeLimit = isset($membershipDetails['package'])
                  ? (int) $membershipDetails['package']['employees_limit']
                  : 0;

              $whatsappAddonLimit = WhatsappAddon::whereHas('whatsappUser', function ($q) use ($owner) {
                  $q->where('user_id', $owner->id);
              })->where('status', WhatsappAddon::STATUS_APPROVED)
                  ->where(function ($q) {
                      $q->whereNull('expire_date')
                        ->orWhere('expire_date', '>=', now());
                  })->sum('qty');

              $employeeAddonLimit = EmployeeAddon::activeFor($owner->id)->sum('qty');

              $whatsappQuota = (int) ($baseWhatsappLimit + $whatsappAddonLimit + $employeeAddonLimit);
              $employeeQuota = (int) ($baseEmployeeLimit + $employeeAddonLimit);

              // OPTIMIZATION: Cache permissions separately since they change less frequently
              // Permissions cache with longer TTL (30 minutes vs 5 minutes for profile)
              $permissionsCacheKey = "user:permissions:{$user->id}:{$owner->id}";
              $permissionsCacheTtl = 21600; // 6 hours

            if ($useOptimizations) {
                // Try to get permissions from cache first
                $permissions = Cache::get($permissionsCacheKey);

                if ($permissions === null) {
                    // Set team ID for Spatie permissions (important for multi-tenant scenarios)
                    $teamId = method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $owner->id;
                    app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

                    // Preload permissions via eager loading to cut N+1s from Spatie
                    $user->load(['roles.permissions', 'permissions']);

                    $permissions = $user->getAllPermissions()->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'name_ar' => $permission->name_ar ?? null,
                            'name_en' => $permission->name_en ?? null,
                            'description' => $permission->description ?? null,
                        ];
                    })->values()->toArray();

                    // Cache permissions separately with longer TTL
                    Cache::put($permissionsCacheKey, $permissions, $permissionsCacheTtl);
                }
            } else {
                // Original code path without optimizations
                $user->load(['roles.permissions', 'permissions']);
                $permissions = $user->getAllPermissions()->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'name_ar' => $permission->name_ar ?? null,
                        'name_en' => $permission->name_en ?? null,
                        'description' => $permission->description ?? null,
                    ];
                })->values()->toArray();
            }

            // Compile user data (keep the logged-in user's identity, but reflect owner's membership)
            $userData = [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'account_type' => $user->account_type,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? null,
                'address' => $user->address ?? null,
                'city' => $user->city ?? null,
                'state' => $user->state ?? null,
                'country' => $user->country ?? null,
                'zip_code' => $user->zip_code ?? null,
                'profile_image' => $user->profile_image ? url('/') . '/assets/front/img/user/' . $user->profile_image : null,
                'membership' => $membershipDetails,
                'is_free_plan' => $isFreePlan,
                'has_active_membership' => !$isExpired && $membership && (int) $membership->status === 1,
                'message' => $user->message ?? null,
                'created_at' => $user->created_at,
                  'updated_at' => $user->updated_at,
                  'domain' => $domain ? $domain->custom_name : "https://{$owner->username}.taearif.com/",
                  'onboarding_completed' => $user->onboarding_completed ?? false,
                  'company_name' => $companyName,
                  'whatsapp' => [
                      'quota' => $whatsappQuota,
                      'usage' => $owner->whatsapp_usage,
                      'max_whatsapp_numbers' => (isset($membershipDetails['package']) ? $membershipDetails['package']['whatsapp_numbers_limit'] : 0),
                      'is_over_limit' => $owner->whatsapp_usage >= $whatsappQuota,
                  ],
                  'employees' => [
                      'quota' => $employeeQuota,
                      'usage' => $owner->employee_usage,
                      'max_employees' => (isset($membershipDetails['package']) ? $membershipDetails['package']['employees_limit'] : 0),
                      'is_over_limit' => $owner->employee_usage >= $employeeQuota,
                    // Use eager loaded counts if available, otherwise fallback to queries
                    'active_count' => $useOptimizations && isset($owner->active_employees_count)
                        ? $owner->active_employees_count
                        : $owner->employees()->where('active', true)->count(),
                    'total_count' => $useOptimizations && isset($owner->total_employees_count)
                        ? $owner->total_employees_count
                        : $owner->employees()->count(),
                ],
                'permissions' => $permissions,
            ];

            // Cache the result if optimizations are enabled
            if ($useOptimizations) {
                Cache::put($cacheKey, $userData, $cacheTtl);
            }

            return response()->json([
                'status' => 'success',
                'data' => $userData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Mark message as read and update user profile
    // read_message
    public function read_message(ReadMessageRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Update message to null
        $user->message = null;
        $user->save();

        return response()->json(['message' => 'Message marked as read']);
    }

    private function createDefaultMenuJson(User $user)
    {
        $defaultMenuJson = json_encode([
            [
                "text" => "Home",
                "href" => "",
                "icon" => "empty",
                "target" => "_self",
                "title" => "",
                "type" => "home"
            ]
            // ,[
            //     "text" => "About",
            //     "href" => "",
            //     "icon" => "empty",
            //     "target" => "_self",
            //     "title" => "",
            //     "type" => "About"
            // ],
            // [
            //     "text" => "Contact",
            //     "href" => "",
            //     "icon" => "empty",
            //     "target" => "_self",
            //     "title" => "",
            //     "type" => "contact"
            // ]
        ]);

        // Create menu and assign it to the user
        $menu = new \App\Models\User\Menu();
        $menu->user_id = $user->id;
        $menu->language_id = Language::where('is_default', 1)->value('id'); // or loop languages
        $menu->menus = $defaultMenuJson;
        $menu->save();

        // Save setting (optional)
        ApiMenuSetting::create([
            'user_id' => $user->id,
            'menu_position' => 'top',
            'menu_style' => 'default',
            'mobile_menu_type' => 'slide',
            'is_sticky' => true,
            'is_transparent' => false,
            'status' => true,
        ]);
    }

    private function updateUserMenu($userId, $languageId)
    {
        $realEstateMenu = [
            ["text" => "الصفحة الرئيسية", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "home"],
            ["text" => "اتصل بنا", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "contact"]
        ];

        $menuJson = json_encode($realEstateMenu, JSON_UNESCAPED_UNICODE);

        $existingMenu = Menu::where([
            'user_id' => $userId,
            'language_id' => $languageId
        ])->first();

        if ($existingMenu) {
            $existingMenu->menus = $menuJson;
            $existingMenu->save();
        } else {
            Menu::create([
                'user_id' => $userId,
                'language_id' => $languageId,
                'menus' => $menuJson
            ]);
        }
    }

    private function invalidCurrencyResponse($message)
    {
        return response()->json(['status' => 'error', 'message' => __($message)], 400);
    }

    private function formatCurrency($amount, $be)
    {
        return ($be->base_currency_text_position == 'left' ? $be->base_currency_text . ' ' : '') . $amount . ($be->base_currency_text_position == 'right' ? ' ' . $be->base_currency_text : '');
    }

    private function create_website($request, $transaction_id, $transaction_details, $amount, $be, $password, $welcome_message)
    {
        return DB::transaction(function () use ($request, $transaction_id, $transaction_details, $amount, $be, $password, $welcome_message) {


            $deLang = User\Language::firstOrFail();
            $deLang_arabic = User\Language::where('user_id', 0)->firstOrFail();
            $deLanguageNames = json_decode($deLang->keywords, true);
            $deLanguageNames_arabic = json_decode($deLang_arabic->keywords, true);

            $menus = '[
                {"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},
                {"text":"About","href":"","icon":"empty","target":"_self","title":"","type":"custom","children":[
                    {"text":"Team","href":"","icon":"empty","target":"_self","title":"","type":"team"},
                    {"text":"Career","href":"","icon":"empty","target":"_self","title":"","type":"career"},
                    {"text":"FAQ","href":"","icon":"empty","target":"_self","title":"","type":"faq"}
                ]},
                {"text":"Services","href":"","icon":"empty","target":"_self","title":"","type":"services"},
                {"text":"Blog","href":"","icon":"empty","target":"_self","title":"","type":"blog"},
                {"text":"Contact","href":"","icon":"empty","target":"_self","title":"","type":"contact"}
            ]';

            $menus_ar = '[
                {"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},
                {"text":"About","href":"","icon":"empty","target":"_self","title":"","type":"custom","children":[
                    {"text":"Team","href":"","icon":"empty","target":"_self","title":"","type":"team"},
                    {"text":"Career","href":"","icon":"empty","target":"_self","title":"","type":"career"},
                    {"text":"FAQ","href":"","icon":"empty","target":"_self","title":"","type":"faq"}
                ]},
                {"text":"Services","href":"","icon":"empty","target":"_self","title":"","type":"services"},
                {"text":"Blog","href":"","icon":"empty","target":"_self","title":"","type":"blog"},
                {"text":"Contact","href":"","icon":"empty","target":"_self","title":"","type":"contact"}
            ]';

            $menus = json_decode($menus, true);
            foreach (array_column($menus, 'text') as $key => $menu) {
                if ($menu == 'Home' && array_key_exists($menu, $deLanguageNames)) {
                    $menus[$key]['text'] = $deLanguageNames[$menu];
                }
                if ($menu == 'About') {
                    $menus[$key]['text'] = array_key_exists('About', $deLanguageNames) ? $deLanguageNames['About'] : 'About';
                    if (isset($menus[$key]['children']) && count($menus[$key]['children']) > 0) {
                        foreach (array_column($menus[$key]['children'], 'text') as $k => $value) {
                            if (in_array($value, ['Team', 'Career', 'FAQ']) && array_key_exists($value, $deLanguageNames)) {
                                $menus[$key]['children'][$k]['text'] = $deLanguageNames[$value];
                            }
                        }
                    }
                }
                if (in_array($menu, ['Services', 'Blog', 'Contact']) && array_key_exists($menu, $deLanguageNames)) {
                    $menus[$key]['text'] = $deLanguageNames[$menu];
                }
            }

            $menus_arabic = json_decode($menus_ar, true);
            foreach (array_column($menus_arabic, 'text') as $key => $menu) {
                if ($menu == 'Home' && array_key_exists($menu, $deLanguageNames_arabic)) {
                    $menus_arabic[$key]['text'] = $deLanguageNames_arabic[$menu];
                }
                if ($menu == 'About') {
                    $menus_arabic[$key]['text'] = array_key_exists('About', $deLanguageNames_arabic) ? $deLanguageNames_arabic['About'] : 'About';
                    if (isset($menus_arabic[$key]['children']) && count($menus_arabic[$key]['children']) > 0) {
                        foreach (array_column($menus_arabic[$key]['children'], 'text') as $k => $value) {
                            if (in_array($value, ['Team', 'Career', 'FAQ']) && array_key_exists($value, $deLanguageNames_arabic)) {
                                $menus_arabic[$key]['children'][$k]['text'] = $deLanguageNames_arabic[$value];
                            }
                        }
                    }
                }
                if (in_array($menu, ['Services', 'Blog', 'Contact']) && array_key_exists($menu, $deLanguageNames_arabic)) {
                    $menus_arabic[$key]['text'] = $deLanguageNames_arabic[$menu];
                }
            }
            $menus = json_encode($menus);
            $menus_arabic = json_encode($menus_arabic);


            if (session()->has('lang')) {
                $currentLang = Language::where('code', session()->get('lang'))->first();
            } else {
                $currentLang = Language::where('is_default', 1)->first();
            }


            $bs = $currentLang->basic_setting;
            $token = md5(time() . $request['username'] . $request['email']);
            $verification_link = "<a href='" . url('register/mode/' . $request['mode'] . '/verify/' . $token) . "'>" .
                "<button type=\"button\" class=\"btn btn-primary\">Click Here</button>" .
                "</a>";
            $user = User::where('username', $request['username']);

            if ($user->count() == 0) {
                $user = User::create([
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'company_name' => $request['company_name'],
                    'email' => $request['email'],
                    'phone' => $request['phone'],
                    'username' => $request['username'],
                    'password' => bcrypt($password),
                    'status' => $request["status"],
                    'message' => $welcome_message,
                    'address' => $request["address"] ? $request["address"] : null,
                    'city' => $request["city"] ? $request["city"] : null,
                    'state' => $request["district"] ? $request["district"] : null,
                    'country' => $request["country"] ? $request["country"] : null,
                    'verification_link' => $token,
                    'referral_code' => $request['referral_code'] ?? strtoupper(Str::random(8)),
                    'referred_by' => $request['referred_by'] ?? null,
                    // Optional attributes
                    'industry_type' => $request['industry_type'] ?? null,
                    'company_size'  => $request['company_size'] ?? null,
                ]);

                $deLang = User\Language::firstOrFail();
                $deLang_arabic = User\Language::where('user_id', 0)->firstOrFail();
                $langCount = User\Language::where('user_id', $user->id)->where('is_default', 1)->count();
                if ($langCount == 0) {
                    $lang = new User\Language;
                    $lang->name = $deLang->name;
                    $lang->code = $deLang->code;
                    $lang->is_default = 1;
                    $lang->rtl = $deLang->rtl;
                    $lang->user_id = $user->id;
                    $lang->keywords = $deLang->keywords;
                    $lang->save();

                    // $lang_ar = new User\Language;
                    // $lang_ar->name = $deLang_arabic->name;
                    // $lang_ar->code = $deLang_arabic->code;
                    // $lang_ar->is_default = 1;
                    // $lang_ar->rtl = $deLang_arabic->rtl;
                    // $lang_ar->user_id = $user->id;
                    // $lang_ar->keywords = $deLang_arabic->keywords;
                    // $lang_ar->save();

                    $htext = new HomePageText;
                    $htext->language_id = $lang->id;
                    $htext->user_id = $user->id;
                    $htext->save();

                    $umenu = new Menu();
                    $umenu->language_id = $lang->id;
                    $umenu->user_id = $user->id;
                    $umenu->menus = $menus;
                    $umenu->save();

                    // $umenu = new Menu();
                    // $umenu->language_id = $lang_ar->id;
                    // $umenu->user_id = $user->id;
                    // $umenu->menus = $menus_arabic;
                    // $umenu->save();
                }

                // --- Begin: Basic Settings Record ---
                // Basic Settings Json
                $basicSettingsJson = '{
                    "favicon": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
                    "breadcrumb": "https://codecanyon8.kreativdev.com/estaty/assets/img/hero/static/6574372e0ad77.jpg",
                    "logo": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
                    "preloader": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
                    "base_color": "0003FF",
                    "secondary_color": "00F5E5",
                    "theme": "home13",
                    // "email": "F.a.t-550@hotmail.com",
                    "from_name": null,
                    "is_quote": "1",
                    "qr_image": "6727bead51be1.png",
                    "qr_color": "000000",
                    "qr_size": "248",
                    "qr_style": "square",
                    "qr_eye_style": "square",
                    "qr_margin": "0",
                    "qr_text": null,
                    "qr_text_color": "000000",
                    "qr_text_size": "15",
                    "qr_text_x": "50",
                    "qr_text_y": "50",
                    "qr_inserted_image": null,
                    "qr_inserted_image_size": "20",
                    "qr_inserted_image_x": "50",
                    "qr_inserted_image_y": "50",
                    "qr_type": "default",
                    "qr_url": "https:\/\/taearif.com\/rangs",
                    "whatsapp_status": "0",
                    "whatsapp_number": null,
                    "whatsapp_header_title": null,
                    "whatsapp_popup_status": "0",
                    "whatsapp_popup_message": null,
                    "disqus_status": "0",
                    "disqus_short_name": null,
                    "analytics_status": "0",
                    "measurement_id": null,
                    "pixel_status": "0",
                    "pixel_id": null,
                    "tawkto_status": "0",
                    "tawkto_direct_chat_link": null,
                    "custom_css": null,
                    "website_title": "شركة ليرا العقارية",
                    "base_currency_symbol": "$",
                    "base_currency_symbol_position": "left",
                    "base_currency_text": "USD",
                    "base_currency_rate": null,
                    "base_currency_text_position": null,
                    "is_recaptcha": "0",
                    "google_recaptcha_site_key": null,
                    "google_recaptcha_secret_key": null,
                    "adsense_publisher_id": null,
                    "timezone": "1",
                    "features_section_image": null,
                    "cv": null,
                    "cv_original": null,
                    "email_verification_status": "1",
                    "cookie_alert_status": "0",
                    "cookie_alert_text": null,
                    "cookie_alert_button_text": null,
                    "property_country_status": "1",
                    "property_state_status": "1",
                    "short_description": "شركة ليرا هي شركة عقارية مبتكرة ومتخصصة في تقديم خدمات العقارات بجودة عالية وحلول مهنية. تتميز الشركة بتقديم مجموعة واسعة من العقارات سواء كانت سكنية أو تجارية، وتهدف إلى تلبية احتياجات عملائها من خلال توفير خيارات متنوعة تناسب كافة الأذواق والميزانيات.",
                    "industry_type": "Real Estate Company"
                }';

                $basicSettingsArray = json_decode($basicSettingsJson, true);

                if (isset($basicSettingsArray['id'])) {
                    unset($basicSettingsArray['id']);
                }

                // Override the email with the user's email and user id
                $basicSettingsArray['email'] = $user->email;
                $basicSettingsArray['user_id'] = $user->id;

                User\BasicSetting::create($basicSettingsArray);
                // --- End: Basic Settings Record ---

                // --- Begin: Portfolio Category and Portfolio Records ---
                // Retrieve the default language for the user
                $defaultLanguage = User\Language::where('user_id', $user->id)->where('is_default', 1)->first();
                $secondLanguage = User\Language::where('user_id', $user->id)->where('is_default', 0)->first();

                // Insert portfolio category
                $portfolioCategoryJson = '{
                    "user_id": "",
                    "name": "Consulting",
                    "status": "1",
                    "language_id": "",
                    "serial_number": "1",
                    "created_at": "2021-11-14 17:59:12",
                    "updated_at": "2022-03-12 06:53:01",
                    "featured": "1"
                }';

                $portfolioCategoryArray = json_decode($portfolioCategoryJson, true);
                if (isset($portfolioCategoryArray['id'])) {
                    unset($portfolioCategoryArray['id']);
                }
                $portfolioCategoryArray['user_id'] = $user->id;
                $portfolioCategoryArray['language_id'] = $defaultLanguage->id;
                $portfolioCategory = User\PortfolioCategory::create($portfolioCategoryArray);

                // Insert portfolio
                $portfolioJson = <<<'JSON'
                {
                    "title": "Free Consulting",
                    "slug": "free-consulting-free-consulting",
                    "user_id": "",
                    "image": "1671874201.jpg",
                    "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                    "serial_number": "1",
                    "status": "1",
                    "client_name": "Jorgan Roy",
                    "start_date": "2021-11-19",
                    "submission_date": "2021-02-09",
                    "website_link": "http://example.com/",
                    "featured": "1",
                    "language_id": "",
                    "category_id": "",
                    "meta_keywords": null,
                    "meta_description": null,
                    "created_at": "2021-11-15 00:01:09",
                    "updated_at": "2022-12-24 05:30:01"
                }
                JSON;


                $portfolioArray = json_decode($portfolioJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error: ' . json_last_error_msg());
                }

                // Loop 6 times to insert 6 unique portfolio records.
                for ($i = 1; $i <= 6; $i++) {
                    $portfolioArray = json_decode($portfolioJson, true);
                    if (isset($portfolioArray['id'])) {
                        unset($portfolioArray['id']);
                    }

                    // Set unique title and slug by appending the loop counter.
                    $portfolioArray['title'] = "Free Consulting " . $i;
                    $portfolioArray['slug']  = "free-consulting-" . $i;

                    // Override foreign keys with the actual values.
                    $portfolioArray['user_id'] = $user->id;
                    $portfolioArray['language_id'] = $defaultLanguage->id;
                    $portfolioArray['category_id'] = $portfolioCategory->id;

                    // Ensure that the 'featured' field is set if not already.
                    if (!isset($portfolioArray['featured']) || $portfolioArray['featured'] === '') {
                        $portfolioArray['featured'] = 0;
                    }

                    // Create the portfolio record.
                    User\Portfolio::create($portfolioArray);
                }
                // --- End: Insert Repeated Portfolio Records ---

                // Use nowdoc syntax for valid JSON
                $servicesJson = <<<'JSON'
                [
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "MOBILE APPS",
                        "slug": "mobile-apps",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fab fa-accusoft"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "WEB DEVELOPMENT",
                        "slug": "web-development",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-arrows-alt"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "MARKETTING SEO",
                        "slug": "marketting-seo",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-bell-slash"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "GRAPHIC DESIGN",
                        "slug": "graphic-design",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fas fa-address-card"
                    },
                    {
                        "id": "",
                        "image": "1647182306.jpg",
                        "name": "PLUGIN DEVELOPMENT",
                        "slug": "plugin-development",
                        "content": "<p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><img src=\"http://businesso.local/assets/front/img/summernote/6191ff8f5b3f6.jpg\" style=\"width:100%;\" alt=\"6191ff8f5b3f6.jpg\" /><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\"><br /></span></p><p style=\"text-align:justify;color:rgb(0,0,0);font-family:'Open Sans', Arial, sans-serif;\"><span style=\"font-family:Verdana;\">There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.</span></p>",
                        "serial_number": "4",
                        "featured": "1",
                        "detail_page": "1",
                        "lang_id": "",
                        "user_id": "",
                        "meta_keywords": null,
                        "meta_description": null,
                        "created_at": "2021-11-14 23:35:13",
                        "updated_at": "2021-11-17 00:57:44",
                        "icon": "fab fa-accusoft"
                    }
                ]
                JSON;

                // Decode JSON and check for errors
                $servicesArray = json_decode($servicesJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for services: ' . json_last_error_msg());
                } else {
                    foreach ($servicesArray as $serviceData) {
                        if (empty($serviceData['id'])) {
                            unset($serviceData['id']);
                        }
                        $serviceData['lang_id'] = $defaultLanguage->id;
                        $serviceData['user_id'] = $user->id;

                        // Insert into the user_services table.
                        \App\Models\User\UserService::create($serviceData);
                    }
                }

                // --- End: Insert Repeated UserService Records ---

                //  insert into user_members table
                $membersJson = <<<'JSON'
                [
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Stuart Clark",
                        "rank": "CEO, Rolan",
                        "image": "77fd8c98cbac033eb9208e5d41671290e9ae65e6.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Federico Cheisa",
                        "rank": "Manager, Rolan",
                        "image": "ce38744ba92b841ec371066096cfae32ac3fb433.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Dani Olmo",
                        "rank": "Developer, Rolan",
                        "image": "189ff0cdf780a59aa414f4c5422075b884a5f67b.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Thiago Silva",
                        "rank": "Designer, Rolan",
                        "image": "bd39661d73f980587b075d225a2ff5a3991c1964.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "1"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Thiago Motta",
                        "rank": "Team Leader, Rolan",
                        "image": "716ece3ac2eefb7a7267c6489d6e99354e8f18c3.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "0"
                    },
                    {
                        "language_id": "",
                        "user_id": "",
                        "name": "Chielini",
                        "rank": "Developer, Rolan",
                        "image": "54fab799139d4f815ff7601249f4bb81feb98d29.jpg",
                        "facebook": "http://example.com/",
                        "twitter": "http://example.com/",
                        "instagram": "http://example.com/",
                        "linkedin": "http://example.com/",
                        "featured": "0"
                    }
                ]
                JSON;

                $membersArray = json_decode($membersJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for members: ' . json_last_error_msg());
                } else {
                    foreach ($membersArray as $memberData) {

                        $memberData['language_id'] = $defaultLanguage->id;
                        $memberData['user_id'] = $user->id;

                        \App\Models\User\Member::create($memberData);
                    }
                }

                // --- Insert Home Page Texts ---
                $homePageTextsJson = <<<'JSON'
                [
                {
                    "about_image": "62381226ecd01.png",
                    "about_image_two": null,
                    "about_title": "حول رينجز",
                    "about_subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "about_content": "لكن لكي أفهم من أين وُلد كل هذا الخطأ ، سأفتح الأمر برمته في موقع تجول وآلام الناس المدح ، وسأشرح تلك الأشياء التي قالها مخترع الحقيقة والمهندس المعماري. من الحياة المباركة. فلا أحد يرفض المتعة نفسها لأنها متعة ، ولكن لأن الأشياء العظيمة تتبعها",
                    "about_button_text": "يتعلم أكثر",
                    "about_button_url": "http://example.com/",
                    "about_video_image": null,
                    "about_video_url": null,
                    "skills_title": null,
                    "skills_subtitle": null,
                    "skills_content": null,
                    "service_title": "خدمات الشركة",
                    "service_subtitle": "نحن نقدم خدمة حصرية",
                    "experience_title": null,
                    "experience_subtitle": null,
                    "portfolio_title": "حالات مميزة",
                    "portfolio_subtitle": "نلقي نظرة على الحالات",
                    "view_all_portfolio_text": "مشاهدة الكل",
                    "testimonial_title": "شهاداتنا",
                    "testimonial_subtitle": "يقول العملاء عنا",
                    "testimonial_image": "622ded84e62f8.jpg",
                    "blog_title": "أخبارنا ومدونتنا",
                    "blog_subtitle": "كل واحد التحديثات",
                    "view_all_blog_text": "مشاهدة الكل",
                    "team_section_title": "أعضاء الفريق",
                    "team_section_subtitle": "تعرف على خبرائنا المحترفين",
                    "video_section_image": null,
                    "video_section_url": null,
                    "video_section_title": null,
                    "video_section_subtitle": null,
                    "video_section_text": null,
                    "video_section_button_text": null,
                    "video_section_button_url": null,
                    "why_choose_us_section_image": "301b9239f5acc672e89ea19ccf4f7263207458394.jpg",
                    "why_choose_us_section_image_two": null,
                    "why_choose_us_section_title": "لماذا نحن الأفضل؟",
                    "why_choose_us_section_subtitle": "لدينا أسباب كثيرة لاختيارنا",
                    "why_choose_us_section_text": "لكنك ستفهم من أين يسعد كل هذا الخطأ المولود باتهام وألم أولئك الذين يمتدحونها ، وكل عمليات الاغتصاب التي هي من مخترع الحقيقة هذا وإن جاز التعبير.\r\nلكنك ستفهم من أين يسعد كل هذا الخطأ المولود بالاتهام والتصفيق",
                    "why_choose_us_section_button_text": "خدماتنا",
                    "why_choose_us_section_button_url": "http://example.com/",
                    "why_choose_us_section_video_image": "d1d67774227ae9d427fd1d391b578eb76c7ac1412.jpg",
                    "why_choose_us_section_video_url": "https://www.youtube.com/watch?v=pWOv9xcoMeY",
                    "faq_section_image": "6195e2a1d0dce3.png",
                    "faq_section_title": "التعليمات",
                    "faq_section_subtitle": "أسئلة مكررة",
                    "work_process_section_title": "كيف نعمل",
                    "work_process_section_subtitle": "عملية العمل لدينا",
                    "work_process_section_text": "",
                    "work_process_section_img": "00733bb91bb288918e16a40dfc1516839e550f91.jpg",
                    "work_process_section_video_img": null,
                    "work_process_section_video_url": null,
                    "quote_section_title": "إقتبس",
                    "quote_section_subtitle": "ولكن لمعرفة من الذي ولد كل هذا الخطأ sitevoluac",
                    "counter_section_image": "622df3492b4f1.jpg",
                    "work_process_btn_txt": "ابدأ مشروعًا",
                    "work_process_btn_url": "http://example.com/",
                    "contact_section_image": "63b41b3407c93.png",
                    "contact_section_title": "Requst a Quote",
                    "contact_section_subtitle": "Lorem ipsum dolor sit amet",
                    "feature_item_title": null,
                    "new_item_title": null,
                    "newsletter_title": null,
                    "newsletter_subtitle": null,
                    "bestseller_item_title": null,
                    "special_item_title": null,
                    "flashsale_item_title": null,
                    "toprated_item_title": null,
                    "category_section_title": null,
                    "category_section_subtitle": null,
                    "rooms_section_title": null,
                    "rooms_section_subtitle": null,
                    "rooms_section_content": null,
                    "featured_course_section_title": null,
                    "newsletter_image": null,
                    "featured_section_title": null,
                    "featured_section_subtitle": null,
                    "causes_section_title": null,
                    "causes_section_subtitle": null,
                    "about_snd_button_text": null,
                    "about_snd_button_url": null,
                    "skills_image": null,
                    "job_education_title": null,
                    "job_education_subtitle": null,
                    "newsletter_snd_image": null,
                    "donor_title": null,
                    "years_of_expricence": null,
                    "featured_property_title": null,
                    "property_title": null,
                    "city_title": null,
                    "city_subtitle": null,
                    "project_title": null,
                    "project_subtitle": null,
                    "testimonial_text": null
                },
                {
                    "about_image": "62381226ecd01.png",
                    "about_image_two": null,
                    "about_title": "About Us",
                    "about_subtitle": "Professional Business Guidance Agency",
                    "about_content": "Sedut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi\n\nDoloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi\n\n Business &amp; Consulting Agency\n Awards Winning Business Comapny\n Business &amp; Consulting Agency\n Awards Winning Business Comapny",
                    "about_button_text": "Learn More",
                    "about_button_url": "http://example.com/",
                    "about_video_image": null,
                    "about_video_url": null,
                    "skills_title": null,
                    "skills_subtitle": null,
                    "skills_content": null,
                    "service_title": "Our Services",
                    "service_subtitle": "Lorem ipsum dolor sit amet consectetur e.",
                    "experience_title": null,
                    "experience_subtitle": null,
                    "portfolio_title": "Featured Cases",
                    "portfolio_subtitle": "Take a Look at the Cases",
                    "view_all_portfolio_text": "View All",
                    "testimonial_title": "Client's Say",
                    "testimonial_subtitle": "Lorem ipsum dolor sit",
                    "testimonial_image": "6195e2885a64b.jpg",
                    "blog_title": "Our News and Blog",
                    "blog_subtitle": "Every Single Updates",
                    "view_all_blog_text": "View All",
                    "team_section_title": "Our Team",
                    "team_section_subtitle": "Lorem ipsum dolor sit amet",
                    "video_section_image": "4e075552eb76535027695b317dcc7cfed9e1e3cf.jpg",
                    "video_section_url": "https://www.youtube.com/watch?v=IjlYXtI2-GU",
                    "video_section_title": "Industrial Services That We Provide",
                    "video_section_subtitle": null,
                    "video_section_text": "Lorem ipsum dolor sit amet, consectetur adipi sicing Sed do eiusmod tempor incididunt labore et dolore magna aliqua. Ut enim ad minim veniam quis nostrud exercitation ullamco",
                    "video_section_button_text": null,
                    "video_section_button_url": null,
                    "why_choose_us_section_image": "301b9239f5acc672e89ea9ccf4f7263207458394.jpg",
                    "why_choose_us_section_image_two": null,
                    "why_choose_us_section_title": "Why We Are Best ?",
                    "why_choose_us_section_subtitle": "We Have Many Reasons to Choose Us",
                    "why_choose_us_section_text": "Sedut perspiciatis unde omnis iste natus error sit voluptat em accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi.\r\nSedut perspiciatis unde omnis iste natus error sit voluptat em accusantium doloremque laudantium, totam raperiaeaque ipsa quae ab illo inventore veritatis et quasi",
                    "why_choose_us_section_button_text": "Our Services",
                    "why_choose_us_section_button_url": "http://example.com/",
                    "why_choose_us_section_video_image": "d1d67774227ae9d427fdd391b578eb76c7ac1412.jpg",
                    "why_choose_us_section_video_url": "https://www.youtube.com/watch?v=pWOv9xcoMeY",
                    "faq_section_image": "6195e2ad0dce3.png",
                    "faq_section_title": "FAQ",
                    "faq_section_subtitle": "Frequently Asked Questions",
                    "work_process_section_title": "25 Years Of Experience",
                    "work_process_section_subtitle": "Best SEO Optimization Agency",
                    "work_process_section_text": "Lorem ipsum dolor sit amet, consectetur adipisicing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis",
                    "work_process_section_img": null,
                    "work_process_section_video_img": null,
                    "work_process_section_video_url": null,
                    "quote_section_title": "Start Work With us",
                    "quote_section_subtitle": "Lorem ipsum dolor sit amet",
                    "language_id": "",
                    "user_id": "",
                    "created_at": "2021-11-17 00:30:27",
                    "updated_at": "2024-11-03 20:14:04",
                    "counter_section_image": "622f3061a2073.jpg",
                    "work_process_btn_txt": "Learn More",
                    "work_process_btn_url": "http://example.com/",
                    "contact_section_image": "63b41b21c45a9.png",
                    "contact_section_title": "Requst a Quote",
                    "contact_section_subtitle": "Lorem ipsum dolor sit amet",
                    "feature_item_title": null,
                    "new_item_title": null,
                    "newsletter_title": null,
                    "newsletter_subtitle": null,
                    "bestseller_item_title": null,
                    "special_item_title": null,
                    "flashsale_item_title": null,
                    "toprated_item_title": null,
                    "category_section_title": null,
                    "category_section_subtitle": null,
                    "rooms_section_title": null,
                    "rooms_section_subtitle": null,
                    "rooms_section_content": null,
                    "featured_course_section_title": null,
                    "newsletter_image": null,
                    "featured_section_title": null,
                    "featured_section_subtitle": null,
                    "causes_section_title": null,
                    "causes_section_subtitle": null,
                    "about_snd_button_text": null,
                    "about_snd_button_url": null,
                    "skills_image": null,
                    "job_education_title": null,
                    "job_education_subtitle": null,
                    "newsletter_snd_image": null,
                    "donor_title": null,
                    "years_of_expricence": null,
                    "featured_property_title": null,
                    "property_title": null,
                    "city_title": null,
                    "city_subtitle": null,
                    "project_title": null,
                    "project_subtitle": null,
                    "testimonial_text": null
                }
                ]
                JSON;

                $homePageTextsArray = json_decode($homePageTextsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for home page texts: ' . json_last_error_msg());
                } else {
                    foreach ($homePageTextsArray as $textData) {
                        // Set the language and user IDs
                        $textData['language_id'] = $defaultLanguage->id;
                        $textData['user_id'] = $user->id;
                        \App\Models\User\HomePageText::create($textData);
                    }
                }

                // --- Insert Hero Sliders ---
                $heroSlidersJson = <<<'JSON'
                [
                {
                    "language_id": "",
                    "img": "784ffa3036c249fd132041bf56701406720e3e23.jpg",
                    "title": "Corporate Law Firms",
                    "subtitle": "25 Years Of Experience In Law Solutiuons",
                    "btn_name": "Our Services",
                    "btn_url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:46"
                },
                {
                    "language_id": "",
                    "img": "37db1e96370fe3a98b1814d4fb6922822419bf3a.jpg",
                    "title": "Corporate Law Firms",
                    "subtitle": "25 Years Of Experience In Law Solutiuons",
                    "btn_name": "Our Services",
                    "btn_url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:54"
                },
                {
                    "language_id": "",
                    "img": "9d5005c0ad6235fadbdec1e5f181c85f9cf51841.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "1",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:12"
                },
                {
                    "language_id": "",
                    "img": "784ffa3036c249fd132041bf56701406720e3e23.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:46"
                },
                {
                    "language_id": "",
                    "img": "37db1e96370fe3a98b1814d4fb6922822419bf3a.jpg",
                    "title": "شركات قانون الشركات",
                    "subtitle": "25 عاما من الخبرة في الحلول القانونية",
                    "btn_name": "خدماتنا",
                    "btn_url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2022-03-13 08:14:12",
                    "updated_at": "2022-03-13 08:14:54"
                }
                ]
                JSON;

                $heroSlidersArray = json_decode($heroSlidersJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for hero sliders: ' . json_last_error_msg());
                } else {
                    foreach ($heroSlidersArray as $sliderData) {
                        // Set the correct language and user IDs
                        $sliderData['language_id'] = $defaultLanguage->id;
                        $sliderData['user_id'] = $user->id;
                        \App\Models\User\HeroSlider::create($sliderData);
                    }
                }

                // --- Insert Socials ---
                $socialsJson = <<<'JSON'
                [
                {
                    "icon": "fab fa-facebook-f",
                    "url": "http://example.com/",
                    "serial_number": "1",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:10",
                    "updated_at": "2021-11-17 06:34:10"
                },
                {
                    "icon": "fab fa-twitter",
                    "url": "http://example.com/",
                    "serial_number": "2",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:18",
                    "updated_at": "2021-11-17 06:34:18"
                },
                {
                    "icon": "fab fa-linkedin-in",
                    "url": "http://example.com/",
                    "serial_number": "3",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:26",
                    "updated_at": "2021-11-17 06:34:26"
                },
                {
                    "icon": "fab fa-dribbble",
                    "url": "http://example.com/",
                    "serial_number": "4",
                    "user_id": "",
                    "created_at": "2021-11-17 06:34:48",
                    "updated_at": "2021-11-17 06:34:48"
                },
                {
                    "icon": "fab fa-behance",
                    "url": "http://example.com/",
                    "serial_number": "5",
                    "user_id": "",
                    "created_at": "2021-11-17 06:35:01",
                    "updated_at": "2021-11-17 06:35:01"
                }
                ]
                JSON;

                $socialsArray = json_decode($socialsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for socials: ' . json_last_error_msg());
                } else {
                    foreach ($socialsArray as $socialData) {
                        // Assign the current user's id to each record
                        $socialData['user_id'] = $user->id;
                        \App\Models\User\Social::create($socialData);
                    }
                }

                // --- Insert Testimonials ---
                $testimonialsJson = <<<'JSON'
                [
                ]
                JSON;

                $testimonialsArray = json_decode($testimonialsJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for testimonials: ' . json_last_error_msg());
                } else {
                    foreach ($testimonialsArray as $testimonialData) {
                        // Set the language and user IDs from your existing variables
                        $testimonialData['lang_id'] = $defaultLanguage->id;
                        $testimonialData['user_id'] = $user->id;
                        // Optionally remove timestamps if they're not in the fillable array
                        unset($testimonialData['created_at'], $testimonialData['updated_at']);
                        \App\Models\User\UserTestimonial::create($testimonialData);
                    }
                }

                // --- Insert Work Processes ---
                $workProcessJson = <<<'JSON'
                [
                    {
                        "icon": "far fa-bookmark",
                        "title": "Have A Coffee",
                        "text": "Doloremque laudantium totam raperiaeaqu ipsa quae ab illo inventore veritatis et quasi",
                        "serial_number": "1",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:09:36",
                        "updated_at": "2022-03-12 06:48:44"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "Meet With Advisors",
                        "text": "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque",
                        "serial_number": "2",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-bullseye",
                        "title": "Achieve Your Goals",
                        "text": "Quis autem vel eum iure reprehenderit qui ieas voluptate velit esse quam nihil mole",
                        "serial_number": "3",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:12:07",
                        "updated_at": "2021-11-16 19:12:07"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "Meet With Advisors",
                        "text": "Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque",
                        "serial_number": "4",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-coffee",
                        "title": "تناول القهوة",
                        "text": "إن ألم أولئك الذين يثنون على كل شيء هو نفس الأشياء التي منه هو مخترع الحقيقة وإذا جاز التعبير.",
                        "serial_number": "1",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:09:36",
                        "updated_at": "2021-11-16 19:13:43"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "لقاء مع المستشارين",
                        "text": "ولكن لكي نفهم من أين يولد كل هذا الخطأ ممن يتهمهم باللذة والألم",
                        "serial_number": "2",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    },
                    {
                        "icon": "fas fa-bullseye",
                        "title": "حقق اهدافك",
                        "text": "ولكن من يدين بحق من يريد أن تكون المتعة مجرد جماعية",
                        "serial_number": "3",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:12:07",
                        "updated_at": "2021-11-16 19:12:07"
                    },
                    {
                        "icon": "far fa-user",
                        "title": "لقاء مع المستشارين",
                        "text": "ولكن لكي نفهم من أين يولد كل هذا الخطأ ممن يتهمهم باللذة والألم",
                        "serial_number": "4",
                        "user_id": "",
                        "language_id": "",
                        "created_at": "2021-11-16 19:11:13",
                        "updated_at": "2021-11-16 19:11:13"
                    }
                ]
                JSON;

                $workProcessArray = json_decode($workProcessJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error for work processes: ' . json_last_error_msg());
                } else {
                    foreach ($workProcessArray as $workProcessData) {
                        // Set the current language and user IDs
                        $workProcessData['language_id'] = $defaultLanguage->id;
                        $workProcessData['user_id'] = $user->id;
                        // Remove extra keys that are not fillable
                        unset($workProcessData['created_at'], $workProcessData['updated_at']);
                        \App\Models\User\WorkProcess::create($workProcessData);
                    }
                }

                // --- Begin: Insert Property Categories Records into user_property_categories ---
                $propertyCategoriesJson = <<<'JSON'
                [
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "شقة",
                        "slug": "شقة",
                        "image": "67be66fe9fa44.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "0",
                        "created_at": "2025-02-03 13:51:00",
                        "updated_at": "2025-02-26 03:57:34"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "دور",
                        "slug": "دور",
                        "image": "67a0add555128.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "1",
                        "created_at": "2025-02-03 13:51:49",
                        "updated_at": "2025-02-03 13:38:29"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "فيلا",
                        "slug": "فيلا",
                        "image": "67a0adfc6b72b.jpg",
                        "status": "1",
                        "featured": "0",
                        "serial_number": "2",
                        "created_at": "2025-02-03 13:52:28",
                        "updated_at": "2025-02-03 13:38:32"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "commercial",
                        "name": "ارض",
                        "slug": "ارض",
                        "image": "67a0c6fc91f80.png",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "3",
                        "created_at": "2025-02-03 13:39:08",
                        "updated_at": "2025-02-03 13:39:21"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "residential",
                        "name": "دوبلكس",
                        "slug": "دوبلكس",
                        "image": "67be671e3439b.jpg",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "4",
                        "created_at": "2025-02-03 13:39:46",
                        "updated_at": "2025-02-26 03:58:06"
                    },
                    {
                        "user_id": "",
                        "language_id": "",
                        "type": "commercial",
                        "name": "تاون هاوس",
                        "slug": "تاون-هاوس",
                        "image": "67a0c73cc5b90.png",
                        "status": "1",
                        "featured": "1",
                        "serial_number": "5",
                        "created_at": "2025-02-03 13:40:12",
                        "updated_at": "2025-02-03 13:41:12"
                    }
                ]
                JSON;

                $propertyCategoriesArray = json_decode($propertyCategoriesJson, true);

                foreach ($propertyCategoriesArray as $catData) {
                    // Insert category for Default Language (Arabic)
                    \App\Models\User\RealestateManagement\Category::create([
                        'user_id' => $user->id,
                        'language_id' => $defaultLanguage->id,
                        'type' => $catData['type'],
                        'name' => $catData['name'], // Arabic Name
                        'slug' => $catData['slug'], // Arabic Slug
                        'image' => $catData['image'],
                        'status' => $catData['status'],
                        'featured' => $catData['featured'],
                        'serial_number' => $catData['serial_number']
                    ]);

                    // Insert category for Secondary Language (English)
                    // \App\Models\User\RealestateManagement\Category::create([
                    //     'user_id' => $user->id,
                    //     'language_id' => $secondLanguage->id,
                    //     'type' => $catData['type'],
                    //     'name' => $catData['name'], // Keeping same name for now
                    //     'slug' => $catData['slug'], // Keeping same slug for now
                    //     'image' => $catData['image'],
                    //     'status' => $catData['status'],
                    //     'featured' => $catData['featured'],
                    //     'serial_number' => $catData['serial_number']
                    // ]);
                }

                // --- End: Insert Property Categories Records into user_property_categories ---




                //
                //
                //
                //


                // --- email verification ---
                $ubs = BasicSetting::select('email_verification_status')->first();

                if ($ubs->email_verification_status == 1) {
                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $user->email,
                        'toName' => $user->first_name,
                        'customer_name' => $user->first_name,
                        'verification_link' => $verification_link,
                        'website_title' => $bs->website_title,
                        'templateType' => 'email_verification',
                        'type' => 'emailVerification'
                    ];
                    $mailer->mailFromAdmin($data);
                }

                $package = Package::findOrFail($request['package_id']);
                if (is_array($request)) {
                    $conversation_id = array_key_exists('conversation_id', $request) ? $request['conversation_id'] : null;
                } else {
                    $conversation_id = null;
                }

                Membership::create([
                    'package_price' => $package->price,
                    'discount' => session()->has('coupon_amount') ? session()->get('coupon_amount') : 0,
                    'coupon_code' => session()->has('coupon') ? session()->get('coupon') : NULL,
                    'price' => $amount,
                    'currency' => $be->base_currency_text ? $be->base_currency_text : "USD",
                    'currency_symbol' => $be->base_currency_symbol ? $be->base_currency_symbol : $be->base_currency_text,
                    'payment_method' => $request["payment_method"],
                    'transaction_id' => $transaction_id ? $transaction_id : 0,
                    'status' => $request["status"] ? $request["status"] : 0,
                    'is_trial' => $request["package_type"] == "regular" ? 0 : 1,
                    'trial_days' => $request["package_type"] == "regular" ? 0 : $request["trial_days"],
                    'receipt' => $request["receipt_name"] ? $request["receipt_name"] : null,
                    'transaction_details' => $transaction_details ? $transaction_details : null,
                    'settings' => json_encode($be),
                    'package_id' => $request['package_id'],
                    'user_id' => $user->id,
                    'start_date' => Carbon::parse($request['start_date']),
                    'expire_date' => Carbon::parse($request['expire_date']),
                    'conversation_id' => $conversation_id
                ]);

                // Handle package upgrade and disable maintenance mode if needed
                $membershipService = app(\App\Services\MembershipService::class);
                $membershipService->handlePackageUpgrade($user, $request['package_id'], 'api');

                $features = json_decode($package->features, true);
                $features[] = "Contact";
                UserPermission::create([
                    'package_id' => $request['package_id'],
                    'user_id' => $user->id,
                    'permissions' => json_encode($features)
                ]);

                $payment_keywords = ['flutterwave', 'razorpay', 'paytm', 'paystack', 'instamojo', 'stripe', 'paypal', 'mollie', 'mercadopago', 'authorize.net', 'phonepe'];
                foreach ($payment_keywords as $key => $value) {
                    UserPaymentGeteway::create([
                        'title' => null,
                        'user_id' => $user->id,
                        'details' => null,
                        'keyword' => $value,
                        'subtitle' => null,
                        'name' => ucfirst($value),
                        'type' => 'automatic',
                        'information' => null
                    ]);
                }

                $templates = ['email_verification', 'product_order', 'reset_password', 'room_booking', 'room_booking', 'payment_received', 'payment_cancelled', 'course_enrolment', 'course_enrolment_approved', 'course_enrolment_rejected', 'donation', 'donation_approved'];
                foreach ($templates as $key => $val) {
                    UserEmailTemplate::create([
                        'user_id' => $user->id,
                        'email_type' => $val,
                        'email_subject' => null,
                        'email_body' => '<p></p>',
                    ]);
                }

                $homeSection = new HomeSection();
                $homeSection->user_id = $user->id;
                $homeSection->save();

                UserShopSetting::create([
                    'user_id' => $user->id,
                    'is_shop' => 1,
                    'catalog_mode' => 0,
                    'item_rating_system' => 1,
                    'tax' => 0,
                ]);
            }

            if (Session::has('coupon')) {
                $coupon = Coupon::where('code', Session::get('coupon'))->first();
                $coupon->total_uses = $coupon->total_uses + 1;
                $coupon->save();
            }

            $short_description = "اهلا بك في موقعنا";
            SEO::create(
                [
                    'home_meta_description' => $short_description
                ]
            );
            return $user;
        });
    }
}
