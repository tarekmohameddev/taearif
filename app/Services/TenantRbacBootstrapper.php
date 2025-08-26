<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TenantRbacBootstrapper
{
    public function run(int $tenantId): void
    {
        $lock = Cache::lock("rbac:seed:{$tenantId}", 10);

        $lock->block(10, function () use ($tenantId) {
            $tenant        = User::findOrFail($tenantId);
            $targetVersion = (int) config('rbac.rbac_version', 1);
            if ((int) $tenant->rbac_version >= $targetVersion && $tenant->rbac_seeded_at) return;

            $guard = (string) config('rbac.guard', 'sanctum');
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            DB::transaction(function () use ($tenant, $tenantId, $targetVersion, $guard) {
                // 1) ensure GLOBAL permissions (see §2 below)
                $allPermNames = collect(config('rbac.permissions', []))->filter()->unique()->values();
                $perms = $this->ensurePermissions($guard, $allPermNames);

                // 2) ensure roles for this tenant — include 'owner' explicitly
                $templates = collect(config('rbac.role_templates', []));    // e.g. ['manager'=>[...], 'agent'=>[...]]
                $roleNames = $templates->keys()->merge(['owner'])->unique()->values()->all();
                $roles     = $this->ensureRoles($tenantId, $guard, $roleNames);

                // 3) apply templates (do NOT touch owner here)
                foreach ($templates as $roleName => $permNames) {
                    $role = $roles->get($roleName);
                    if (!$role) continue;
                    $rolePerms = $perms->only($permNames)->values();
                    $role->syncPermissions($rolePerms);
                }

                // 4) assign owner BY INSTANCE (prevents a create path)
                $ownerRole = $roles->get('owner');
                if ($ownerRole && !$tenant->hasRole($ownerRole)) {
                    $tenant->assignRole($ownerRole);
                }

                $tenant->forceFill([
                    'rbac_version'   => $targetVersion,
                    'rbac_seeded_at' => now(),
                ])->saveQuietly();
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Ensure all given permissions exist (prefer global/null team if present),
     * otherwise create tenant-scoped permissions, and return a Collection<Permission>.
     */
    protected function ensurePermissions(string $guard, array|Collection $names): Collection
    {
        $names = collect($names)->filter()->unique()->values();
        if ($names->isEmpty()) return collect();
    
        $existing = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');
    
        $map = collect();
        foreach ($names as $n) {
            $map->put($n, $existing->get($n) ?? Permission::findOrCreate($n, $guard));
        }
        return $map;
    }


    /**
     * Ensure tenant roles exist and return a Collection<Role>.
     */
    // private function ensureRoles(int $tenantId, array $roleNames): Collection
    // {
    //     if (empty($roleNames)) return collect();

    //     $existing = Role::query()
    //         ->where('team_id', $tenantId)
    //         ->whereIn('name', $roleNames)
    //         ->get()
    //         ->keyBy('name');

    //     $created = collect();
    //     foreach ($roleNames as $name) {
    //         if (!$existing->has($name)) {
    //             $created->push(
    //                 Role::create([
    //                     'name'       => $name,
    //                     'guard_name' => 'sanctum',
    //                     'team_id'    => $tenantId,
    //                 ])
    //             );
    //         }
    //     }

    //     return $existing->values()->merge($created);
    // }
    protected function ensureRoles(int $teamId, string $guard, array $names): \Illuminate\Support\Collection
    {
        $names = collect($names)->filter()->unique()->values();
        if ($names->isEmpty()) return collect();

        $byName = collect();

        foreach ($names as $name) {
            $role = Role::query()
                ->where('name', $name)
                ->where('guard_name', $guard)
                ->where('team_id', $teamId)
                ->first();

            if (!$role) {
                try {
                    $role = Role::create([
                        'name'       => $name,
                        'guard_name' => $guard,
                        'team_id'    => $teamId,
                    ]);
                } catch (\Throwable $e) {
                    $role = Role::query()
                        ->where('name', $name)
                        ->where('guard_name', $guard)
                        ->where('team_id', $teamId)
                        ->first();
                }
            }

            if ($role) $byName->put($name, $role);
        }

        return $byName;
    }

}
