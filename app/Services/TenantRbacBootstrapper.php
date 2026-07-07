<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Exceptions\Api\BusinessLogicException;
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
            $registrar = app(PermissionRegistrar::class);

            DB::transaction(function () use ($tenant, $tenantId, $targetVersion, $guard, $registrar) {
                // 1) ensure GLOBAL permissions (see §2 below)
                $allPermNames = collect(config('rbac.permissions', []))->filter()->unique()->values();
                $perms = $this->ensureGlobalPermissions($guard, $allPermNames);

                $existingGlobalNames = Permission::query()
                    ->where('guard_name', $guard)
                    ->whereNull('team_id')
                    ->whereIn('name', $allPermNames)
                    ->pluck('name');

                $missing = $allPermNames->diff($existingGlobalNames)->values();
                if ($missing->isNotEmpty()) {
                    throw BusinessLogicException::make(
                        message: 'RBAC bootstrap failed: missing permissions after seeding.',
                        details: ['missing_permissions' => $missing->all()]
                    );
                }

                // 2) ensure roles for this tenant — include 'owner' explicitly
                $templates = collect(config('rbac.role_templates', []));    // e.g. ['manager'=>[...], 'agent'=>[...]]
                $roleNames = $templates->keys()->merge(['owner'])->unique()->values()->all();
                $roles     = $this->ensureRoles($tenantId, $guard, $roleNames);

                // 3) apply templates (do NOT touch owner here)
                foreach ($templates as $roleName => $permNames) {
                    $role = $roles->get($roleName);
                    if (!$role) continue;
                    $rolePermIds = DB::table('api_permissions')
                        ->where('guard_name', $guard)
                        ->whereNull('team_id')
                        ->whereIn('name', (array) $permNames)
                        ->pluck('id')
                        ->all();

                    // Avoid relying on Spatie's internal permission resolution here; we already resolved stable IDs.
                    DB::table('api_role_has_permissions')
                        ->where('role_id', $role->id)
                        ->delete();

                    foreach ($rolePermIds as $pid) {
                        $row = [
                            'permission_id' => (int) $pid,
                            'role_id'       => (int) $role->id,
                        ];

                        if (Schema::hasColumn('api_role_has_permissions', 'team_id')) {
                            $row['team_id'] = (int) $tenantId;
                        }

                        DB::table('api_role_has_permissions')->insertOrIgnore($row);
                    }
                }

                // 4) assign owner BY INSTANCE (prevents a create path)
                $ownerRole = $roles->get('owner');
                if ($ownerRole && !$tenant->hasRole($ownerRole)) {
                    // Switch to tenant context for role assignment.
                    $registrar->setPermissionsTeamId($tenantId);
                    $tenant->assignRole($ownerRole);
                }

                $tenant->forceFill([
                    'rbac_version'   => $targetVersion,
                    'rbac_seeded_at' => now(),
                ])->saveQuietly();
            });

            $registrar->forgetCachedPermissions();
        });
    }

    /**
     * Ensure all given permissions exist as GLOBAL (team_id NULL) permissions.
     *
     * Returns a map keyed by permission name.
     */
    protected function ensureGlobalPermissions(string $guard, array|Collection $names): Collection
    {
        $names = collect($names)->filter()->unique()->values();
        if ($names->isEmpty()) return collect();

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // Ensure we do not accidentally create tenant-scoped permissions.
        $registrar->setPermissionsTeamId(null);

        $existing = Permission::query()
            ->where('guard_name', $guard)
            ->whereNull('team_id')
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $missing = $names->diff($existing->keys())->values();

        foreach ($missing as $name) {
            DB::table('api_permissions')->insertOrIgnore([
                'name'       => (string) $name,
                'guard_name' => $guard,
                'team_id'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $registrar->forgetCachedPermissions();

        return Permission::query()
            ->where('guard_name', $guard)
            ->whereNull('team_id')
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');
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
