<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Admin Service Provider
 *
 * Handles admin-specific service registrations and configurations
 */
class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        // Merge admin API configuration
        $this->mergeConfigFrom(
            base_path('config/admin-api.php'),
            'admin-api'
        );

        // Register admin-specific services as singletons
        $this->app->singleton(
            \App\Services\Cache\CacheService::class
        );
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        // Configure admin Sanctum guard
        config([
            'sanctum.guard' => [config('admin-api.guard')],
        ]);

        // Load admin API routes
        $this->loadAdminRoutes();
    }

    /**
     * Load admin API routes
     *
     * @return void
     */
    protected function loadAdminRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group(base_path('routes/admin-api.php'));
    }
}

