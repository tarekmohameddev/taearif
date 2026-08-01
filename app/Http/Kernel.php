<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\Cors::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\ConvertArabicNumerals::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\SetTenantSessionDomain::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \App\Http\Middleware\SanctumTokenOnlyForApi::class, // API: Bearer-only; no web guard / TransientToken
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:1000,1',
            'bindings',
            \App\Http\Middleware\SetTenantSessionDomain::class,
            \App\Http\Middleware\SetTenantForPermissions::class,
            \App\Http\Middleware\CompressResponse::class, // Compress API responses
        ],

        'admin-api' => [
            'throttle:admin_api_general',
            'bindings',
        ],
        // 'api' => [
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        //     'throttle:api',
        //     \Illuminate\Routing\Middleware\SubstituteBindings::class,
        // ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'checkpermission' => \App\Http\Middleware\CheckPermission::class,
        'checkAdminApiPermission' => \App\Http\Middleware\CheckAdminApiPermission::class,
        'pbx.secret' => \App\Http\Middleware\VerifyPbxWebhookSecret::class,
        'setlang' => \App\Http\Middleware\SetLangMiddleware::class,
        'setadminlocale' => \App\Http\Middleware\SetAdminLocaleMiddleware::class,
        'checkstatus' => \App\Http\Middleware\CheckStatus::class,
        'userstatus' => \App\Http\Middleware\UserStatus::class,
        'checkUserPermission' => \App\Http\Middleware\CheckPermissionUser::class,
        'Demo' => \App\Http\Middleware\Demo::class,
        'routeAccess' => \App\Http\Middleware\RouteAccess::class,
        'checkWebsiteOwner' => \App\Http\Middleware\CheckWebsiteOwner::class,
        'accountStatus' => \App\Http\Middleware\UserRegisteredUserStatus::class,
        'lfm.path' => \App\Http\Middleware\LfmPath::class,
        'employee.can' => \App\Http\Middleware\EmployeePermission::class,
        'employee.only' => \App\Http\Middleware\EnsureEmployee::class,
        'tenant.only'   => \App\Http\Middleware\EnsureTenant::class,
        // 'tenant.can' => \App\Http\Middleware\TenantPermission::class,
        // EnsureUserIsActive
        'ensureUserIsActive' => \App\Http\Middleware\EnsureUserIsActive::class,
        'audit.ctx' => \App\Http\Middleware\PopulateAuditContext::class,
        'log.employee.activity' => \App\Http\Middleware\LogEmployeeRequestActivity::class,
        'owner-or-can' => \App\Http\Middleware\OwnerOrCan::class,
        'require.active.package' => \App\Http\Middleware\RequireActiveMembership::class,
        'check.maintenance' => \App\Http\Middleware\CheckMaintenanceMode::class,
        'tenant.resolve' => \App\Http\Middleware\TenantResolution::class,
        'owner-rental.auth' => \App\Http\Middleware\OwnerRentalAuth::class,
        'tenant.id.response' => \App\Http\Middleware\AddTenantIdToResponse::class,
        'prevent.swagger.production' => \App\Http\Middleware\PreventSwaggerInProduction::class,

    ];

    /**
     * The priority-sorted list of middleware.
     *
     * This forces non-global middleware to always be in the given order.
     *
     * @var array
     */
    protected $middlewarePriority = [
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\Authenticate::class,
        \Illuminate\Session\Middleware\AuthenticateSession::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        \Illuminate\Auth\Middleware\Authorize::class,
    ];
}
