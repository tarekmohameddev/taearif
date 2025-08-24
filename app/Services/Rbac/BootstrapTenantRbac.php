<?php

namespace App\Services\Rbac;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BootstrapTenantRbac
{
    public function run(User $tenant): void
    {
        // safety: only for tenant accounts
        if (($tenant->account_type ?? 'tenant') !== 'tenant') return;

        // Set the Spatie "team id" (our tenant key = users.id)
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        // Create roles INSIDE THIS TENANT SPACE
        $owner   = Role::firstOrCreate(['name' => 'owner',   'guard_name' => 'sanctum', 'user_id' => $tenant->id]);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'sanctum', 'user_id' => $tenant->id]);
        $agent   = Role::firstOrCreate(['name' => 'agent',   'guard_name' => 'sanctum', 'user_id' => $tenant->id]);
        $viewer  = Role::firstOrCreate(['name' => 'viewer',  'guard_name' => 'sanctum', 'user_id' => $tenant->id]);

        // Map permissions (assumes you've seeded these names)
        $owner->givePermissionTo(Permission::all());

        $manager->syncPermissions([
            'properties.view','properties.create','properties.update','properties.delete',
            'projects.view','projects.create','projects.update','projects.delete',
            'blogs.view','blogs.create','blogs.update','blogs.delete',
            'settings.update',
        ]);

        $agent->syncPermissions([
            'properties.view','properties.create','properties.update',
            'projects.view','projects.create',
            'blogs.view','blogs.create',
        ]);

        $viewer->syncPermissions([
            'properties.view','projects.view','blogs.view',
        ]);

        // Give the tenant user the owner role
        $tenant->assignRole('owner');
    }
}
