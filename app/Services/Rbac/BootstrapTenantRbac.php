<?php

namespace App\Services\Rbac;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class BootstrapTenantRbac
{
    public function run(User $tenant): void
    {
        // Only for tenants
        if (!method_exists($tenant, 'isTenant') || !$tenant->isTenant()) {
            return;
        }

        $tenantId   = (int) $tenant->id;
        $guard      = config('rbac.guard', 'sanctum');
        $permSlugs  = collect(config('rbac.permissions', []))->filter()->unique()->values();
        $templates  = collect(config('rbac.role_templates', []));
        $rbacVerCfg = (int) config('rbac.rbac_version', 1);

        // prevent concurrent seeding for same tenant
        $lock = Cache::lock("rbac:seed:{$tenantId}", 30);
        try {
            if (!$lock->get()) {
                return; // another request is bootstrapping now
            }

            // fast exit if already seeded to same version
            if ((int) ($tenant->rbac_version ?? 0) === $rbacVerCfg) {
                return;
            }

            // set Spatie team context
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            DB::transaction(function () use ($tenant, $tenantId, $guard, $permSlugs, $templates, $rbacVerCfg) {
                // 1) Ensure permissions (prefer global null team if you use them; here we create tenant-scoped)
                $perms = collect();
                foreach ($permSlugs as $name) {
                    $perms->push(
                        Permission::firstOrCreate(
                            ['name' => $name, 'guard_name' => $guard, 'team_id' => $tenantId],
                            []
                        )
                    );
                }

                // 2) Ensure roles & sync templates
                foreach ($templates as $roleName => $wanted) {
                    /** @var \Spatie\Permission\Models\Role $role */
                    $role = Role::firstOrCreate(
                        ['name' => $roleName, 'guard_name' => $guard, 'team_id' => $tenantId],
                        []
                    );

                    // Expand wildcards like "menu.*" / "customers.*"
                    $wanted = Arr::wrap($wanted);
                    $targetPerms = $wanted === ['*']
                        ? $perms
                        : $perms->filter(function ($p) use ($wanted) {
                            foreach ($wanted as $pattern) {
                                if ($pattern === '*') return true;
                                if (str_ends_with($pattern, '.*')) {
                                    $prefix = substr($pattern, 0, -2);
                                    if (str_starts_with($p->name, $prefix . '.')) return true;
                                } elseif ($p->name === $pattern) {
                                    return true;
                                }
                            }
                            return false;
                        });

                    $role->syncPermissions($targetPerms);
                }

                // 3) Assign owner role to the tenant user (idempotent)
                $tenant->assignRole('owner');

                // 4) persist version flags (add columns once)
                if (Schema()->hasColumn('users', 'rbac_version')) {
                    $tenant->forceFill([
                        'rbac_version'   => $rbacVerCfg,
                        'rbac_seeded_at' => Carbon::now(),
                    ])->save();
                }
            });

        } finally {
            optional($lock)->release();
            // important: clear permission cache
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
