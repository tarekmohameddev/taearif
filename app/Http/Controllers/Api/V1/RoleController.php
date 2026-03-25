<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePermissionRequest;
use App\Http\Requests\Api\V1\UpdatePermissionRequest;
use App\Http\Requests\Api\V1\StoreRoleRequest;
use App\Http\Requests\Api\V1\UpdateRoleRequest;
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
            ->with('permissions:id,name,name_ar,name_en')
            ->select('id', 'name', 'name_ar', 'name_en', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        // Add permissions to each role
        $roles->transform(function ($role) {
            $role->permissions_list = $role->permissions->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'name_ar' => $permission->name_ar,
                    'name_en' => $permission->name_en
                ];
            })->toArray();
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
            ->with('permissions:id,name,name_ar,name_en')
            ->select('id', 'name', 'name_ar', 'name_en', 'team_id', 'guard_name', 'created_at', 'updated_at')
            ->findOrFail($id);

        $role->permissions_list = $role->permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'name_ar' => $permission->name_ar,
                'name_en' => $permission->name_en
            ];
        })->toArray();

        return response()->json([
            'status' => 'success',
            'data' => $role
        ]);
    }

    // POST /roles
    public function store(StoreRoleRequest $request)
    {
        $tenantId = $this->tenantId();
        $data = $request->validated();

        // Create the role
        $role = Role::create([
            'user_id' => $tenantId,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'team_id' => $tenantId,
            'guard_name' => 'sanctum'
        ]);

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load permissions for response
        $role->load('permissions:id,name,name_ar,name_en');
        $role->permissions_list = $role->permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'name_ar' => $permission->name_ar,
                'name_en' => $permission->name_en
            ];
        })->toArray();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.created',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'new_values'  => [
                'name' => $role->name,
                'name_ar' => $role->name_ar,
                'name_en' => $role->name_en,
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
    public function update(UpdateRoleRequest $request, $id)
    {
        $tenantId = $this->tenantId();
        $role = Role::where('team_id', $tenantId)->findOrFail($id);
        $data = $request->validated();

        $oldData = [
            'name' => $role->name,
            'name_ar' => $role->name_ar,
            'name_en' => $role->name_en,
            'permissions' => $role->permissions->pluck('name')->toArray()
        ];

        // Update role name
        $role->update([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? $role->name_ar,
            'name_en' => $data['name_en'] ?? $role->name_en
        ]);

        // Update permissions if provided
        if (array_key_exists('permissions', $data)) {
            $permissions = Permission::whereIn('name', $data['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load permissions for response
        $role->load('permissions:id,name,name_ar,name_en');
        $role->permissions_list = $role->permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'name_ar' => $permission->name_ar,
                'name_en' => $permission->name_en
            ];
        })->toArray();

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
                'name_ar' => $role->name_ar,
                'name_en' => $role->name_en,
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
        $tenantId = $this->tenantId();

        // Get both global and tenant-specific permissions
        $permissions = Permission::where(function($query) use ($tenantId) {
                $query->whereNull('team_id') // Global permissions
                      ->orWhere('team_id', $tenantId); // Tenant-specific permissions
            })
            ->select('id', 'name', 'name_ar', 'name_en', 'description', 'team_id')
            ->orderBy('name')
            ->get();

        // Group permissions by resource
        $groupedPermissions = $permissions->groupBy(function($permission) {
            return explode('.', $permission->name)[0];
        });

        return response()->json([
            'status' => 'success',
            'data' => $permissions,
            'grouped' => $groupedPermissions,
            'templates' => $this->getPermissionTemplates()
        ]);
    }

    // POST /permissions
    public function storePermission(StorePermissionRequest $request)
    {
        $tenantId = $this->tenantId();
        $data = $request->validated();

        // Validate against business-action pattern
        $validation = $this->validatePermissionName($data['name']);
        if (!$validation['valid']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid permission format',
                'errors' => $validation['errors'],
                'suggestions' => $validation['suggestions']
            ], 422);
        }

        // Create tenant-specific permission
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'sanctum',
            'team_id' => $tenantId, // Make it tenant-specific
            'description' => $data['description'] ?? null,
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null
        ]);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'permission.created',
            'target_type' => 'api_permissions',
            'target_id'   => $permission->id,
            'new_values'  => [
                'name' => $permission->name,
                'description' => $permission->description
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Permission created successfully',
            'data' => $permission
        ], 201);
    }

    // PUT /permissions/{id}
    public function updatePermission(UpdatePermissionRequest $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $data = $request->validated();

        $oldName = $permission->name;
        $permission->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $permission->description,
            'name_ar' => $data['name_ar'] ?? $permission->name_ar,
            'name_en' => $data['name_en'] ?? $permission->name_en
        ]);

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

    /**
     * Validate permission name against business-action pattern
     */
    private function validatePermissionName($permissionName)
    {
        $errors = [];
        $suggestions = [];

        // Check format: resource.action
        if (!preg_match('/^[a-z][a-z_]*\.[a-z_]+$/', $permissionName)) {
            $errors[] = 'Permission must follow format: resource.action (e.g., properties.view)';
            $suggestions[] = 'Use lowercase letters and dots only';
        }

        $parts = explode('.', $permissionName);
        if (count($parts) !== 2) {
            $errors[] = 'Permission must have exactly one dot separating resource and action';
            return ['valid' => false, 'errors' => $errors, 'suggestions' => $suggestions];
        }

        [$resource, $action] = $parts;

        // Validate resource against business context
        $validResources = [
            'properties', 'projects', 'customers', 'crm', 'content',
            'settings', 'reports', 'analytics', 'users', 'employees',
            'bookings', 'sales', 'leads', 'deals', 'contracts', 'payments',
            'customers_hub_analytics', 'customers_hub_requests',
            'customers_hub_customers', 'customers_hub_pipeline',
            'customers_hub_ai_matching',
        ];

        if (!in_array($resource, $validResources)) {
            $errors[] = "Invalid resource: '{$resource}'";
            $suggestions[] = 'Valid resources: ' . implode(', ', $validResources);
        }

        // Validate action against business actions
        $validActions = [
            'view', 'create', 'update', 'delete', 'approve', 'reject',
            'assign', 'export', 'import', 'manage', 'feature', 'archive',
            'restore', 'followup', 'schedule', 'complete', 'cancel'
        ];

        if (!in_array($action, $validActions)) {
            $errors[] = "Invalid action: '{$action}'";
            $suggestions[] = 'Valid actions: ' . implode(', ', $validActions);
        }

        // Business-specific validation
        if ($resource === 'properties' && in_array($action, ['approve', 'feature'])) {
            // These are valid for properties
        } elseif ($resource === 'customers' && $action === 'followup') {
            // Valid for customer management
        } elseif ($resource === 'crm' && in_array($action, ['assign', 'schedule'])) {
            // Valid for CRM operations
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'suggestions' => $suggestions
        ];
    }

    /**
     * Get permission templates for the business
     */
    private function getPermissionTemplates()
    {
        return [
            'properties' => [
                'view' => 'View property listings',
                'create' => 'Add new properties',
                'update' => 'Edit property details',
                'delete' => 'Remove properties',
                'approve' => 'Approve property listings',
                'feature' => 'Feature/unfeature properties',
                'export' => 'Export property data',
                'manage' => 'Full property management'
            ],
            'projects' => [
                'view' => 'View real estate projects',
                'create' => 'Create new projects',
                'update' => 'Edit project details',
                'delete' => 'Delete projects',
                'assign' => 'Assign projects to agents',
                'approve' => 'Approve project plans',
                'manage' => 'Full project management'
            ],
            'customers' => [
                'view' => 'View customer information',
                'create' => 'Add new customers',
                'update' => 'Edit customer details',
                'delete' => 'Remove customers',
                'assign' => 'Assign customers to agents',
                'followup' => 'Schedule customer follow-ups',
                'export' => 'Export customer data',
                'manage' => 'Full customer management'
            ],
            'crm' => [
                'view' => 'View CRM data',
                'create' => 'Create CRM records',
                'update' => 'Update CRM information',
                'assign' => 'Assign CRM tasks',
                'schedule' => 'Schedule CRM activities',
                'manage' => 'Full CRM management'
            ],
            'reports' => [
                'view' => 'View business reports',
                'create' => 'Generate custom reports',
                'export' => 'Export report data',
                'manage' => 'Manage reporting system'
            ],
            'settings' => [
                'view' => 'View system settings',
                'update' => 'Modify system settings',
                'manage' => 'Full system management'
            ],
            'customers_hub_analytics' => [
                'view'   => 'View Customers Hub analytics pages',
                'create' => 'Create analytics records in Customers Hub',
                'update' => 'Update analytics records in Customers Hub',
                'delete' => 'Delete analytics records in Customers Hub',
            ],
            'customers_hub_requests' => [
                'view'   => 'View customer requests in Customers Hub',
                'create' => 'Create customer requests in Customers Hub',
                'update' => 'Update customer requests in Customers Hub',
                'delete' => 'Delete customer requests in Customers Hub',
            ],
            'customers_hub_customers' => [
                'view'   => 'View customers in Customers Hub',
                'create' => 'Add customers in Customers Hub',
                'update' => 'Edit customers in Customers Hub',
                'delete' => 'Remove customers in Customers Hub',
            ],
            'customers_hub_pipeline' => [
                'view'   => 'View pipeline in Customers Hub',
                'create' => 'Create pipeline stages in Customers Hub',
                'update' => 'Update pipeline stages in Customers Hub',
                'delete' => 'Delete pipeline stages in Customers Hub',
            ],
            'customers_hub_ai_matching' => [
                'view'   => 'View AI Matching pages in Customers Hub',
                'create' => 'Create AI Matching records in Customers Hub',
                'update' => 'Update AI Matching records in Customers Hub',
                'delete' => 'Delete AI Matching records in Customers Hub',
            ],
        ];
    }
}
