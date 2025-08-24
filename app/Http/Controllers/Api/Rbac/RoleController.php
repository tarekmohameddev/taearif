<?php

namespace App\Http\Controllers\Api\Rbac;

use Illuminate\Http\Request;
use App\Support\TenantActivity;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /**
     * Ensure all given permission names exist (prefer global/null team if present),
     * otherwise create tenant-scoped permissions, and return the Permission models.
     *
     * @param  int    $tenantId
     * @param  array  $names
     * @return \Illuminate\Support\Collection<\Spatie\Permission\Models\Permission>
     */
    private function ensurePermissions(int $tenantId, array $names)
    {
        $names = collect($names)->filter()->unique()->values();

        if ($names->isEmpty()) {
            return collect();
        }

        // fetch existing: global (team_id null) OR tenant-scoped (team_id = $tenantId)
        $existing = Permission::query()
            ->whereIn('name', $names)
            ->where(function ($q) use ($tenantId) {
                $q->whereNull('team_id')->orWhere('team_id', $tenantId);
            })
            ->get()
            ->keyBy('name');

        // create missing as tenant-scoped
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

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

    
        $data = $request->validate([
            'name'          => ['required','string','max:191',
                Rule::unique('api_roles','name')->where(fn($q)=>$q->where('team_id',$tenantId)),
            ],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ]);
    
        // ensure/create permissions for this tenant (or reuse global)
        $perms = $this->ensurePermissions($tenantId, $data['permissions'] ?? []);
    
        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'sanctum',
            'team_id'    => $tenantId,
        ]);
    
        if ($perms->isNotEmpty()) {
            // sync by models; team context already set
            $role->syncPermissions($perms);
        }
    
        // emit activity via your single listener
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



    public function update(Request $request, Role $role)
    {
        $tenantId = $this->tenantId($request);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);


        if ((int)$role->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name'          => ['sometimes','string','max:191',
                Rule::unique('api_roles','name')->where(fn($q)=>$q->where('team_id',$tenantId))->ignore($role->id),
            ],
            'permissions'   => ['sometimes','array'],
            'permissions.*' => ['string'],
        ]);

        $old = [
            'name'        => $role->name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ];

        if (array_key_exists('name', $data)) {
            $role->name = $data['name'];
            $role->save();
        }

        if (array_key_exists('permissions', $data)) {
            // ensure/create permissions for this tenant (or reuse global)
            $perms = $this->ensurePermissions($tenantId, $data['permissions'] ?? []);
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


        if ((int) $role->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        if (in_array($role->name, ['owner'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete protected role'], 422);
        }

        $role->delete();

        // TenantActivity

        TenantActivity::emit($request, 'role.deleted', 'api_roles', $role->id, $role->toArray(), null);
        return response()->json(['status' => 'success', 'message' => 'Role deleted']);
    }
}
