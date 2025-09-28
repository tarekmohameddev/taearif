<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use ResolvesTenant;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // GET /roles
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $roles = Role::where('team_id', $tenantId)
            ->with('permissions:id,name')
            ->select('id', 'name', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        // Add permissions to each role
        $roles->transform(function ($role) {
            $role->permissions_list = $role->permissions->pluck('name')->toArray();
            return $role;
        });

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    // GET /roles/{id}
    public function show($id)
    {
        $tenantId = $this->tenantId();

        $role = Role::where('team_id', $tenantId)
            ->with('permissions:id,name')
            ->findOrFail($id);

        $role->permissions_list = $role->permissions->pluck('name')->toArray();

        return response()->json([
            'status' => 'success',
            'data' => $role
        ]);
    }

    // POST /roles
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_roles', 'name')->where(function ($query) use ($tenantId) {
                    return $query->where('team_id', $tenantId);
                })
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:api_permissions,name']
        ]);

        // Create the role
        $role = Role::create([
            'user_id' => $tenantId,
            'name' => $data['name'],
            'team_id' => $tenantId,
            'guard_name' => 'sanctum'
        ]);

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load permissions for response
        $role->load('permissions:id,name');
        $role->permissions_list = $role->permissions->pluck('name')->toArray();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.created',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'new_values'  => [
                'name' => $role->name,
                'permissions' => $role->permissions_list
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    // PUT /roles/{id}
    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        $role = Role::where('team_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_roles', 'name')->where(function ($query) use ($tenantId) {
                    return $query->where('team_id', $tenantId);
                })->ignore($role->id)
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:api_permissions,name']
        ]);

        $oldData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ];

        // Update role name
        $role->update([
            'name' => $data['name']
        ]);

        // Update permissions if provided
        if (array_key_exists('permissions', $data)) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load permissions for response
        $role->load('permissions:id,name');
        $role->permissions_list = $role->permissions->pluck('name')->toArray();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.updated',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'old_values'  => $oldData,
            'new_values'  => [
                'name' => $role->name,
                'permissions' => $role->permissions_list
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role updated successfully',
            'data' => $role
        ]);
    }

    // DELETE /roles/{id}
    public function destroy($id)
    {
        $tenantId = $this->tenantId();

        $role = Role::where('team_id', $tenantId)->findOrFail($id);

        // Check if role is protected
        $protectedRoles = ['owner', 'manager', 'agent'];
        if (in_array($role->name, $protectedRoles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete protected role: ' . $role->name,
                'protected_roles' => $protectedRoles
            ], 403);
        }

        // Check if role is assigned to any users
        $userCount = User::role($role->name, 'sanctum')->count();
        if ($userCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete role that is assigned to ' . $userCount . ' user(s)',
                'assigned_users' => $userCount
            ], 422);
        }

        $oldData = [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ];

        // Remove all permissions from role before deletion
        $role->syncPermissions([]);
        $role->delete();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.deleted',
            'target_type' => 'api_roles',
            'target_id'   => $id,
            'old_values'  => $oldData,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ]);
    }

    // GET /permissions
    public function permissions()
    {
        $permissions = Permission::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

    // POST /permissions
    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:api_permissions,name']
        ]);

        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'sanctum'
        ]);

        ActivityLogger::log([
            'user_id'     => $this->tenantId(),
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'permission.created',
            'target_type' => 'api_permissions',
            'target_id'   => $permission->id,
            'new_values'  => ['name' => $permission->name],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission created successfully',
            'data' => $permission
        ], 201);
    }

    // PUT /permissions/{id}
    public function updatePermission(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:api_permissions,name,' . $permission->id]
        ]);

        $oldName = $permission->name;
        $permission->update(['name' => $data['name']]);

        ActivityLogger::log([
            'user_id'     => $this->tenantId(),
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'permission.updated',
            'target_type' => 'api_permissions',
            'target_id'   => $permission->id,
            'old_values'  => ['name' => $oldName],
            'new_values'  => ['name' => $permission->name],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission updated successfully',
            'data' => $permission
        ]);
    }

    // DELETE /permissions/{id}
    public function destroyPermission($id)
    {
        $permission = Permission::findOrFail($id);

        // Check if permission is assigned to any roles
        $roleCount = Role::permission($permission->name, 'sanctum')->count();
        if ($roleCount > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete permission that is assigned to ' . $roleCount . ' role(s)',
                'assigned_roles' => $roleCount
            ], 422);
        }

        $oldName = $permission->name;
        $permission->delete();

        ActivityLogger::log([
            'user_id'     => $this->tenantId(),
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'permission.deleted',
            'target_type' => 'api_permissions',
            'target_id'   => $id,
            'old_values'  => ['name' => $oldName],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission deleted successfully'
        ]);
    }
}