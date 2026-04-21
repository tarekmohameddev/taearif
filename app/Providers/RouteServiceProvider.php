<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        // Pattern for domain route parameter
        Route::pattern('domain', '[a-z0-9.\-]+');

        // Admin routes are loaded in mapAdminRoutes() method
        // But we need to ensure they're loaded here too for proper registration
        Route::middleware(['web', 'setlang', 'setadminlocale'])
        ->namespace($this->namespace)
        ->prefix('admin')
        ->name('admin.')
        ->group(base_path('routes/admin.php'));

        // Admin dashboard API routes (separate file from public API)
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin-api.php'));

        parent::boot();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * Must be invoked from {@see boot()}; the framework parent does not call this automatically.
     */
    protected function configureRateLimiting(): void
    {
        $this->registerProductionAwareRateLimiters();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapAdminRoutes();
        $this->mapAdminApiRoutes();
        $this->mapMobileRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api') // throttle:api is applied here
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "admin" routes for the application.
     *
     * These routes are prefixed with 'admin' and can have specific middleware.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        // Admin routes are loaded in boot() method to avoid duplication
    }

    /**
     * Define the "admin-api" routes for the application.
     *
     * These routes are for Admin Dashboard API with Sanctum authentication.
     *
     * @return void
     */
    protected function mapAdminApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/admin-api.php'));
    }

    /**
     * Mobile API routes (`routes/mobile.php`, prefix `api/mobile`).
     * Uses 'mobile-api' middleware group which excludes hardcoded throttle.
     */
    protected function mapMobileRoutes(): void
    {
        Route::prefix('api/mobile')
            ->middleware('mobile-api')
            ->namespace($this->namespace)
            ->group(base_path('routes/mobile.php'));
    }

    /**
     * Named rate limiters; outside production all return Limit::none().
     */
    protected function registerProductionAwareRateLimiters(): void
    {
        $only = function (callable $limit) {
            return function (Request $request) use ($limit) {
                if (! app()->environment('production')) {
                    return Limit::none();
                }

                return $limit($request);
            };
        };

        RateLimiter::for('api', $only(function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        }));

        RateLimiter::for('api_mobile', $only(function (Request $request) {
            return Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip());
        }));

        RateLimiter::for('api_tracking', $only(function (Request $request) {
            return Limit::perMinute(100)->by(optional($request->user())->id ?: $request->ip());
        }));

        RateLimiter::for('api_standard_60', $only(function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        }));

        RateLimiter::for('api_tenant_reservations', $only(function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        }));

        RateLimiter::for('api_tenant_job_applications', $only(function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        }));

        RateLimiter::for('admin_api_login', $only(function (Request $request) {
            [$max] = array_map('intval', explode(',', config('admin-api.rate_limits.login'), 2));

            return Limit::perMinute($max)->by($request->ip());
        }));

        RateLimiter::for('admin_api_forgot', $only(function (Request $request) {
            [$max] = array_map('intval', explode(',', config('admin-api.rate_limits.forgot_password'), 2));

            return Limit::perMinute($max)->by($request->ip());
        }));

        RateLimiter::for('admin_api_general', $only(function (Request $request) {
            [$max] = array_map('intval', explode(',', config('admin-api.rate_limits.general'), 2));
            $user = $request->user(config('admin-api.guard'));

            return Limit::perMinute($max)->by(optional($user)->id ?: $request->ip());
        }));
    }
}
