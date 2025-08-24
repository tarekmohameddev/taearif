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
        // lock to avoid double-seeding under concurrency
        $lock = Cache::lock("rbac:seed:{$tenantId}", 10);

        $lock->block(10, function () use ($tenantId) {
            $tenant = User::findOrFail($tenantId); // tenant user has id = tenantId
            $targetVersion = (int) config('rbac.rbac_version', 1);

            // idempotency: skip if already up-to-date
            if ((int) $tenant->rbac_version >= $targetVersion && $tenant->rbac_seeded_at) {
                return;
            }

            // team context for Spatie
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            DB::transaction(function () use ($tenant, $tenantId, $targetVersion) {
                // 1) ensure all permissions exist (reuse global/null team if present)
                $allPermNames = collect(config('rbac.permissions', []))->filter()->unique()->values();
                $perms = $this->ensurePermissions($tenantId, $allPermNames);

                // 2) ensure roles exist for this tenant
                $templates = collect(config('rbac.role_templates', []));
                $roles = $this->ensureRoles($tenantId, $templates->keys()->all());

                // 3) apply role templates (manager/supporter). Owner is fast-pathed anyway.
                foreach ($templates as $roleName => $permNames) {
                    /** @var Role $role */
                    $role = $roles->firstWhere('name', $roleName);
                    if (!$role) continue;

                    $rolePerms = $perms->whereIn('name', $permNames)->values();
                    $role->syncPermissions($rolePerms);
                }

                // 4) assign "owner" to the tenant user (if not already)
                /** @var User $tenant */
                if (!$tenant->hasRole('owner')) {
                    $tenant->assignRole('owner');
                }

                // 5) bump version + seeded timestamp
                $tenant->rbac_version = $targetVersion;
                $tenant->rbac_seeded_at = Carbon::now();
                $tenant->save();
            });

            // 6) clear spatie cache after changes
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * Ensure all given permissions exist (prefer global/null team if present),
     * otherwise create tenant-scoped permissions, and return a Collection<Permission>.
     */
    private function ensurePermissions(int $tenantId, Collection $names): Collection
    {
        if ($names->isEmpty()) return collect();

        $existing = Permission::query()
            ->whereIn('name', $names)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('team_id')->orWhere('team_id', $tenantId);
            })
            ->get()
            ->keyBy('name');

        $created = collect();
        foreach ($names as $name) {
            if (!$existing->has($name)) {
                $created->push(
                    Permission::create([
                        'name'       => $name,
                        'guard_name' => 'sanctum',
                        'team_id'    => $tenantId,
                    ])
                );
            }
        }

        return $existing->values()->merge($created);
    }

    /**
     * Ensure tenant roles exist and return a Collection<Role>.
     */
    private function ensureRoles(int $tenantId, array $roleNames): Collection
    {
        if (empty($roleNames)) return collect();

        $existing = Role::query()
            ->where('team_id', $tenantId)
            ->whereIn('name', $roleNames)
            ->get()
            ->keyBy('name');

        $created = collect();
        foreach ($roleNames as $name) {
            if (!$existing->has($name)) {
                $created->push(
                    Role::create([
                        'name'       => $name,
                        'guard_name' => 'sanctum',
                        'team_id'    => $tenantId,
                    ])
                );
            }
        }

        return $existing->values()->merge($created);
    }
}
