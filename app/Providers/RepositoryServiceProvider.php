<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their concrete implementations
 * Following Dependency Inversion Principle (SOLID)
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Admin\Repositories\AdminRepositoryInterface::class,
            function () {
                return new \App\Domain\Admin\Repositories\AdminRepository(
                    new \App\Domain\Admin\Models\Admin()
                );
            }
        );
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

