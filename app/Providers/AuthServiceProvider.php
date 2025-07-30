<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Allow only Super Admin (role_id === null) to impersonate by default
        // Define a gate for impersonation

        Gate::define('impersonate-users', function (Admin $admin) {
            return is_null($admin->role_id);
        });

        /*
        // If you want to allow certain admin roles later, you can do:
        Gate::define('impersonate-users', function (Admin $admin) {
            if (is_null($admin->role_id)) return true; // super admin
            // For normal admins, check a permission flag on role (example):
            return optional($admin->role)->can_impersonate === 1;
        });
        */
    }
}
