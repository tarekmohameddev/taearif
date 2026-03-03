<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Rbac\SyncRolesRequest;
use App\Http\Requests\Api\Rbac\SyncPermsRequest;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Support\TenantActivity;
use App\Support\CacheInvalidationHelper;

class AssignmentController extends Controller
{
    private function teamFor(Request $request, User $employee = null): int
    {
        $actor = $request->user();
        if ($actor->isTenant()) {
            if ($employee) {
                abort_unless($employee->isEmployee() && $employee->tenant_id === $actor->id, 404);
            }
            return (int) $actor->id;
        }
        abort_unless($actor->isEmployee() && $actor->tenant_id, 403);
        if ($employee) {
            abort_unless($employee->isEmployee() && $employee->tenant_id === $actor->tenant_id, 404);
        }
        return (int) $actor->tenant_id;
    }

    public function showRoles(Request $request, User $employee)
    {
        $teamId = $this->teamFor($request, $employee);
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $teamFk = config('permission.column_names.team_foreign_key', 'team_id');

        $roles = $employee->roles()
            ->wherePivot($teamFk, $teamId)
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'emp_roles' => $roles,
            ],
        ]);
    }

    public function syncRoles(SyncRolesRequest $request, User $employee)
    {
        $teamId = $this->teamFor($request, $employee);
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $teamFk   = config('permission.column_names.team_foreign_key', 'team_id');

        // capture old (team-scoped) roles
        $oldRoles = $employee->roles()
            ->wherePivot($teamFk, $teamId)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        // sync by names; team context already set
        $employee->syncRoles($request->validated()['roles'] ?? []);

        // capture new (team-scoped) roles
        $newRoles = $employee->roles()
            ->wherePivot($teamFk, $teamId)
            ->orderBy('name')
            ->pluck('name')
            ->values()
            ->all();

        // activity
        TenantActivity::emit($request,'employee.roles.synced','users',(int) $employee->id,['roles' => $oldRoles],['roles' => $newRoles]);

        // Clear employee's side menu cache so updated permissions are visible on next load
        CacheInvalidationHelper::clearSideMenusCache((int) $employee->id, $teamId);

        return response()->json(['status' => 'success']);
    }

    public function syncPerms(SyncPermsRequest $request, User $employee)
    {
        $teamId = $this->teamFor($request, $employee);
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $permTable  = config('permission.table_names.permissions');
        $teamFk     = config('permission.column_names.team_foreign_key', 'team_id');


        $oldPerms = $employee->permissions()
            ->wherePivot($teamFk, $teamId)
            ->where(function ($q) use ($permTable, $teamId) {
                $q->whereNull("$permTable.team_id")
                ->orWhere("$permTable.team_id", $teamId);
            })
            ->orderBy("$permTable.name")
            ->pluck("$permTable.name")
            ->values()
            ->all();


        $perms = Permission::query()
            ->whereIn('name', $request->validated()['permissions'] ?? [])
            ->where(function ($q) use ($permTable, $teamId) {
                $q->whereNull("$permTable.team_id")
                ->orWhere("$permTable.team_id", $teamId);
            })
            ->get();

        // --- Sync by models (team context already set in PermissionRegistrar) ---
        $employee->syncPermissions($perms);

        // --- New permissions snapshot (same scoping as above) ---
        $newPerms = $employee->permissions()
            ->wherePivot($teamFk, $teamId)
            ->where(function ($q) use ($permTable, $teamId) {
                $q->whereNull("$permTable.team_id")
                ->orWhere("$permTable.team_id", $teamId);
            })
            ->orderBy("$permTable.name")
            ->pluck("$permTable.name")
            ->values()
            ->all();

        TenantActivity::emit(
            $request,
            'employee.perms.synced',
            'users',
            (int) $employee->id,
            ['perms' => $oldPerms],
            ['perms' => $newPerms]
        );

        // Clear employee's side menu cache so updated permissions are visible on next load
        CacheInvalidationHelper::clearSideMenusCache((int) $employee->id, $teamId);

        return response()->json(['status' => 'success']);
    }

}
