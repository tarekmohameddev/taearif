<?php

namespace App\Http\Controllers\Api\Rbac;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Spatie\Permission\PermissionRegistrar;

class PermissionController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();
        $teamId = $user->tenantOwnerId();
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $perms = $user->getAllPermissions()->pluck('name')->unique()->sort()->values();
        return response()->json(['status' => 'success', 'data' => ['permissions' => $perms]]);
    }

    public function showEmployeeData(Request $request, User $employee)
    {
        $actor  = $request->user();
        $teamId = $actor->isTenant() ? (int) $actor->id : (int) $actor->tenant_id;

        abort_unless($employee->isEmployee() && $employee->tenant_id === $teamId, 404);

        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        $emp_permissions = $employee->getAllPermissions()->pluck('name')->unique()->sort()->values();

        $rolesTable = config('permission.table_names.roles');

        $roles = $employee->roles()
            ->wherePivot('team_id', $teamId)                 // filter on pivot to avoid ambiguity
            ->orderBy($rolesTable . '.name')                 // qualify orderBy
            ->pluck('name')
            ->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'roles'     => $roles,
                'emp_permissions'  => $emp_permissions,
            ],
        ]);
    }
}
