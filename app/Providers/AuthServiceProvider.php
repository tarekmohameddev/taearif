<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\MaintenanceMode;
use App\Policies\MaintenanceModePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        MaintenanceMode::class => MaintenanceModePolicy::class,
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
            // Super admin (no role) can always impersonate
            if (is_null($admin->role_id)) {
                return true;
            }
            
            // Check if admin has "Registered Users" permission
            if (!empty($admin->role) && !empty($admin->role->permissions)) {
                $permissions = json_decode($admin->role->permissions, true);
                return in_array('Registered Users', $permissions);
            }
            
            return false;
        });
        Gate::before(function ($user, string $ability) {
            // Only apply this logic to User models, not Admin models
            if (!$user instanceof User) {
                return null; // Let other gates handle Admin models
            }
            
            // Don't override maintenance mode policies - let them handle their own logic
            if (in_array($ability, ['control', 'disable', 'enable', 'toggle'])) {
                return null; // Let the policy handle this
            }
            
            // Treat both 'tenant' and 'user' as tenant owners
            $isTenant = method_exists($user, 'isTenant')
                ? $user->isTenant()
                : in_array(($user->account_type ?? ''), ['tenant','user'], true);
            return $isTenant ? true : null; // owners can do everything
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
