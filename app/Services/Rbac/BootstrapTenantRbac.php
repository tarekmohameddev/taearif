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
use Illuminate\Support\Collection;

class BootstrapTenantRbac
{
    public function __construct(protected PermissionRegistrar $registrar) {}

    public function run(User $user): void
    {
        $owner = $user->isTenant() ? $user : ($user->tenant_id ? User::find($user->tenant_id) : null);
        if (!$owner) return;

        $teamId = (int) $owner->id;
        $guard  = 'sanctum';

        $this->registrar->setPermissionsTeamId($teamId);

        $lock = \Cache::lock("rbac:seed:$teamId", 60);
        $lock->block(15, function () use ($owner, $guard) {

            DB::transaction(function () use ($owner, $guard) {
                $rolesCatalog = [
                    'owner'   => ['menu.dashboard', 'crm.cards.view', 'crm.cards.create', 'crm.cards.update', 'crm.cards.delete'],
                    'manager' => ['menu.dashboard', 'crm.cards.view', 'crm.cards.create', 'crm.cards.update'],
                    'agent'   => ['menu.dashboard', 'crm.cards.view', 'crm.cards.create'],
                ];


                $permMap = Permission::where('guard_name', $guard)
                    ->whereIn('name', collect($rolesCatalog)->flatten()->unique())
                    ->get()
                    ->keyBy('name');

                $roleMap = collect();
                foreach (array_keys($rolesCatalog) as $roleName) {
                    $roleMap[$roleName] = Role::findOrCreate($roleName, $guard);
                }


                foreach ($rolesCatalog as $roleName => $permNames) {
                    $role = $roleMap[$roleName];
                    $ids  = $permMap->only($permNames)->pluck('id')->all();
                    $role->syncPermissions($ids);
                }


                $ownerRole = $roleMap['owner'] ?? null;
                if ($ownerRole && !$owner->hasRole($ownerRole)) {
                    $owner->assignRole($ownerRole);
                }

                $owner->forceFill([
                    'rbac_version'   => 1,
                    'rbac_seeded_at' => now(),
                ])->saveQuietly();
            });


            $this->registrar->forgetCachedPermissions();
        });
    }
}
