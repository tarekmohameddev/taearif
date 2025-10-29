<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Api\GeneralSetting;
use App\Services\MembershipService;

class CheckMaintenanceMode
{
    protected $membershipService;

    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }

    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     * Add any routes you want to exclude from maintenance mode here.
     *
     * @var array
     */
    protected $except = [
        // Property requests - as requested
        'property-requests/*',

        // API routes
        'api/*',

        // Authentication routes
        'auth/google/*',
        'register',
        'login',
        'auth/forgot-password',
        'auth/verify-reset-code',

        // Payment callback routes (critical for business)
        '*paytm/*',
        '*razorpay/*',
        '*mercadopago/*',
        '*flutterwave/*',
        '*phonepe/*',
        '*paytabs/*',
        '*iyzico/*',
        '*mollie/*',
        '*arb/*',
        '*membership/*',
        '*room_booking/*',
        '*course-enrolment/*',
        '*cause-donation/*',
        '*item-checkout/*',

        // Static assets
        'assets/*',
        'css/*',
        'js/*',
        'images/*',
        'storage/*',
        'favicon.ico',
        'robots.txt',
        'sitemap.xml',

        // Add more routes here as needed
        // Example: 'contact/*', 'about', 'services/*', etc.
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the current route should be excluded from maintenance mode
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Get the current user (tenant)
        // Phase 2: getUser() now consistently returns User|null
        $user = getUser();

        // If no user found (invalid domain/subdomain), show 404
        if (!$user) {
            return response()->view('errors.404', [], 404);
        }

        // Force enable maintenance mode for free package users
        if (!$this->membershipService->canControlMaintenanceMode($user)) {
            $this->membershipService->enableMaintenanceMode($user);
        }

        // Check if maintenance mode is enabled for this user
        $api_general_settingsData = GeneralSetting::where('user_id', $user->id)->first();

        if ($api_general_settingsData && $api_general_settingsData->maintenance_mode == 1) {
            // Prepare data for maintenance view
            $data = [
                'api_general_settingsData' => $api_general_settingsData,
            ];

            return response()->view('user-front.maintenance_mode', $data);
        }

        return $next($request);
    }
}
