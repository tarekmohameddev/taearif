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

        $this->app->bind(
            \App\Domain\Admin\Repositories\ImpersonationRepositoryInterface::class,
            function () {
                return new \App\Domain\Admin\Repositories\ImpersonationRepository(
                    new \App\Domain\Admin\Models\AdminImpersonation()
                );
            }
        );

        $this->app->bind(
            \App\Domain\User\Repositories\UserRepositoryInterface::class,
            function () {
                return new \App\Domain\User\Repositories\UserRepository(
                    new \App\Domain\User\Models\User()
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

