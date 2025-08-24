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
    public function __construct(
        protected PermissionRegistrar $registrar,
    ) {}

    public function run(User $tenantOrEmployee): void
    {
        $owner = $this->resolveOwner($tenantOrEmployee);
        if (!$owner) {
            return;
        }

        $teamId = (int) $owner->id;
        $guard  = (string) config('rbac.guard', 'sanctum');

        // Scope Spatie to this tenant (safe even if middleware already did it)
        $this->registrar->setPermissionsTeamId($teamId);

        DB::transaction(function () use ($owner, $teamId, $guard) {
            $catalogRoles = (array) config('rbac.roles', []);
            $declaredPerms = collect((array) config('rbac.permissions', []));

            // If the flat list isn't provided, derive it from the roles mapping
            $allPermNames = $declaredPerms->isNotEmpty()
                ? $declaredPerms->filter()->unique()->values()
                : collect($catalogRoles)->flatten(1)->filter()->unique()->values();

            // Ensure ALL permissions exist (prefer global, else tenant-scoped)
            $permMapByName = $this->ensurePermissions($teamId, $guard, $allPermNames);

            // Ensure ALL roles exist in this tenant
            $roleMapByName = $this->ensureRoles($teamId, $guard, array_keys($catalogRoles));

            // Sync each role's permissions according to strategy
            $strategy = (string) config('rbac.sync_strategy', 'additive'); // additive|enforce

            foreach ($catalogRoles as $roleName => $permNames) {
                $role = $roleMapByName->get($roleName);
                if (!$role) {
                    continue;
                }

                $desiredPerms = collect($permNames)
                    ->filter()
                    ->unique()
                    ->map(fn ($n) => $permMapByName->get($n))
                    ->filter()
                    ->values();

                if ($strategy === 'enforce') {
                    $role->syncPermissions($desiredPerms);
                } else {
                    // additive: only add what's missing; keep any custom perms
                    $currentNames = $role->permissions()->pluck('name')->all();
                    $toAdd = $desiredPerms->filter(fn (Permission $p) => !in_array($p->name, $currentNames, true));
                    if ($toAdd->isNotEmpty()) {
                        $role->givePermissionTo($toAdd);
                    }
                }
            }

            // Ensure the tenant owner has the owner role
            $ownerRoleName = (string) config('rbac.owner_role', 'owner');
            if ($roleMapByName->has($ownerRoleName) && !$owner->hasRole($ownerRoleName)) {
                $owner->assignRole($ownerRoleName);
            }

            // Mark tenant as up to date (caller/middleware can also set this)
            $owner->forceFill([
                'rbac_version'   => (int) config('rbac.version', 1),
                'rbac_seeded_at' => now(),
            ])->saveQuietly();
        });
    }

    /**
     * If an employee is passed, return their tenant owner; otherwise the user itself.
     */
    protected function resolveOwner(User $user): ?User
    {
        $isTenant   = method_exists($user, 'isTenant')   ? $user->isTenant()   : (($user->account_type ?? 'tenant') === 'tenant');
        $isEmployee = method_exists($user, 'isEmployee') ? $user->isEmployee() : (($user->account_type ?? '') === 'employee');

        if ($isTenant) {
            return $user;
        }

        if ($isEmployee) {
            if (method_exists($user, 'tenant') && $user->tenant) {
                return $user->tenant;
            }
            return $user->tenant_id ? User::find($user->tenant_id) : null;
        }

        return null;
    }

    /**
     * Ensure all roles exist for this tenant; return map: name => Role
     */
    protected function ensureRoles(int $teamId, string $guard, array $names): Collection
    {
        $names = collect($names)->filter()->unique()->values();
        if ($names->isEmpty()) {
            return collect();
        }

        $existing = Role::query()
            ->where('guard_name', $guard)
            ->where('team_id', $teamId)
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $created = collect();
        foreach ($names as $name) {
            if (!$existing->has($name)) {
                $created->push(Role::create([
                    'name'       => $name,
                    'guard_name' => $guard,
                    'team_id'    => $teamId,
                ]));
            }
        }

        return $existing->values()->merge($created)->keyBy('name');
    }

    /**
     * Ensure all permissions exist (prefer global/null team); return map: name => Permission
     */
    protected function ensurePermissions(int $teamId, string $guard, Collection $names): Collection
    {
        $names = $names->filter()->unique()->values();
        if ($names->isEmpty()) {
            return collect();
        }

        $preferGlobal = (bool) config('rbac.prefer_global_permissions', true);

        // Global (team_id null)
        $global = Permission::query()
            ->where('guard_name', $guard)
            ->whereNull('team_id')
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        // Tenant-scoped
        $tenant = Permission::query()
            ->where('guard_name', $guard)
            ->where('team_id', $teamId)
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

        $map = collect();

        foreach ($names as $n) {
            if ($preferGlobal && $global->has($n)) {
                $map->put($n, $global->get($n));
                continue;
            }
            if ($tenant->has($n)) {
                $map->put($n, $tenant->get($n));
                continue;
            }
            // Create tenant-scoped if missing globally & locally
            $perm = Permission::create([
                'name'       => $n,
                'guard_name' => $guard,
                'team_id'    => $teamId,
            ]);
            $map->put($n, $perm);
        }

        return $map;
    }

}
