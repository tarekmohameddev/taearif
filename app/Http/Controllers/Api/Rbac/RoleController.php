<?php

namespace App\Http\Controllers\Api\Rbac;

use Illuminate\Http\Request;
use App\Support\TenantActivity;
use Illuminate\Validation\Rule;
use App\Http\Requests\Api\Rbac\StoreRbacRoleRequest;
use App\Http\Requests\Api\Rbac\UpdateRbacRoleRequest;
use App\Services\ActivityLogger;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Collection;
class RoleController extends Controller
{

    /**
     * Ensure all given permission names exist (prefer global/null team if present),
     * otherwise create tenant-scoped permissions, and return the Permission models.
     *
     * @param  int    $teamId
     * @param  string $guard
     * @param  array|string|Collection $names
     * @return \Illuminate\Support\Collection<\Spatie\Permission\Models\Permission>
     */
    protected function ensurePermissions(int $teamId, string $guard, $names): Collection
    {
        // Normalize input to a collection
        $names = is_string($names) ? [$names] : collect($names)->filter()->unique()->values();

        if ($names->isEmpty()) {
            return collect();
        }

        $preferGlobal = (bool) config('rbac.prefer_global_permissions', true);

        $global = Permission::query()
            ->where('guard_name', $guard)
            ->whereNull('team_id')
            ->whereIn('name', $names)
            ->get()
            ->keyBy('name');

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
            $perm = Permission::create([
                'name'       => $n,
                'guard_name' => $guard,
                'team_id'    => $teamId,
            ]);
            $map->put($n, $perm);
        }

        return $map;
    }

    private function tenantId(Request $request): int
    {
        $u = $request->user();
        return $u->isTenant() ? (int) $u->id : (int) $u->tenant_id;
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);


        $perPage = max(1, (int) $request->integer('per_page', 15));

        $q = Role::query()
            ->where('team_id', $tenantId)
            ->with(['permissions:id,name']);

        if ($search = trim((string) $request->get('q'))) {
            $q->where('name', 'like', "%{$search}%");
        }

        $roles = $q->orderBy('name')->paginate($perPage);

        $items = $roles->getCollection()->map(function (Role $r) {
            return [
                'id'          => $r->id,
                'name'        => $r->name,
                'guard_name'  => $r->guard_name,
                'permissions' => $r->permissions->pluck('name')->values(),
                'created_at'  => $r->created_at,
                'updated_at'  => $r->updated_at,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'roles' => $items,
                'pagination' => [
                    'total'        => $roles->total(),
                    'per_page'     => $roles->perPage(),
                    'current_page' => $roles->currentPage(),
                    'last_page'    => $roles->lastPage(),
                    'from'         => $roles->firstItem(),
                    'to'           => $roles->lastItem(),
                ],
            ],
        ]);
    }

    public function store(StoreRbacRoleRequest $request)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $data = $request->validated();

        // Ensure/create permissions for this tenant (or reuse global)
        $perms = $this->ensurePermissions($tenantId, 'sanctum', $data['permissions'] ?? []);

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'sanctum',
            'team_id'    => $tenantId,
        ]);

        if ($perms->isNotEmpty()) {
            // Sync by models; team context already set
            $role->syncPermissions($perms);
        }

        // Emit activity via your single listener
        TenantActivity::emit($request, 'role.created', 'api_roles', $role->id, null, [
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        ], 201);
    }

    public function update(UpdateRbacRoleRequest $request, Role $role)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        if ((int)$role->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $data = $request->validated();

        if (array_key_exists('name', $data) && $role->name === 'owner' && $data['name'] !== 'owner') {
            return response()->json(['status' => 'error', 'message' => 'Cannot rename protected role'], 422);
        }

        $old = [
            'name'        => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ];

        if (array_key_exists('name', $data)) {
            $role->name = $data['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $data)) {
            // Ensure/create permissions for this tenant (or reuse global)
            $perms = $this->ensurePermissions($tenantId, 'sanctum', $data['permissions'] ?? []);
            $role->syncPermissions($perms);
        }

        TenantActivity::emit($request, 'role.updated', 'api_roles', $role->id, $old, [
            'name'        => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'guard_name'  => $role->guard_name,
                'permissions' => $role->permissions()->pluck('name')->values(),
            ],
        ]);
    }

    public function destroy(Request $request, Role $role)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        if ((int)$role->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if ($role->name === 'owner') {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete protected role'], 422);
        }

        $oldData = [
            'id'          => $role->id,
            'name'        => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ];

        $role->syncPermissions([]);
        $role->delete();

        TenantActivity::emit($request, 'role.deleted', 'api_roles', $role->id, $oldData, null);

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted',
            'data' => [
                'id' => $role->id,
            ],
        ]);
    }
}
