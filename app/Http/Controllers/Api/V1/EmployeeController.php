<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;


class EmployeeController extends Controller
{
    use ResolvesTenant;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // GET /employees
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $q = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->when($request->filled('q'), function ($qb) use ($request) {
                $s = trim($request->q);
                $qb->where(function($w) use ($s){
                    $w->where('first_name','like',"%$s%")
                      ->orWhere('last_name','like',"%$s%")
                      ->orWhere('email','like',"%$s%")
                      ->orWhere('phone','like',"%$s%");
                });
            })
            ->when($request->filled('active'), fn($qb) => $qb->where('active', (bool)$request->boolean('active')))
            ->orderByDesc('id');

        $employees = $q->paginate((int)($request->per_page ?? 20));
        
        // Add roles and permissions to each employee
        $employees->getCollection()->transform(function ($employee) use ($tenantId) {
            // Set tenant context for Spatie
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
            
            $employee->roles = $employee->roles->pluck('name', 'id');
            $employee->permissions = $employee->getPermissionNames();
            return $employee;
        });

        return response()->json($employees);
    }

    // GET /employees/{id}
    public function show($id)
    {
        $tenantId = $this->tenantId();
        
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        
        // Add roles and permissions
        $employee->roles = $employee->roles->pluck('name', 'id');
        $employee->permissions = $employee->getPermissionNames();

        return response()->json([
            'status' => 'success',
            'data' => $employee
        ]);
    }

    // POST /employees
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'first_name' => ['nullable','string','max:120'],
            'last_name'  => ['nullable','string','max:120'],
            'email'      => ['required','email','max:255', Rule::unique('users','email')],
            'phone'      => ['nullable','string','max:50'],
            'password'   => ['required','string','min:6'],
            'active'     => ['boolean'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer','exists:api_roles,id'], // Use api_roles
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        // Create employee as a User with account_type = 'employee'
        $employee = User::create([
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'active'     => $data['active'] ?? true,
            'tenant_id'  => $tenantId,
            'account_type' => 'employee',
            'status'     => 1,
        ]);

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Assign roles if provided
        if (!empty($data['role_ids'])) {
            // Ensure roles belong to same tenant
            $availableRoles = Role::where('team_id', $tenantId)->whereIn('id', $data['role_ids'])->get();
            $requestedRoles = $data['role_ids'];
            $availableRoleIds = $availableRoles->pluck('id')->toArray();
            $missingRoles = array_diff($requestedRoles, $availableRoleIds);
            
            if (!empty($missingRoles)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Some roles do not exist for your tenant',
                    'missing_role_ids' => $missingRoles,
                    'available_roles' => Role::where('team_id', $tenantId)->select('id', 'name')->get()
                ], 422);
            }
            
            // Assign roles using Spatie
            $employee->syncRoles($availableRoles);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'role.assigned',
                'target_type' => 'users',
                'target_id'   => $employee->id,
                'new_values'  => ['roles' => $availableRoleIds],
            ]);
        }

        // Assign permissions if provided
        if (!empty($data['permissions'])) {
            // Assign permissions using Spatie
            $employee->syncPermissions($data['permissions']);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'permissions.assigned',
                'target_type' => 'users',
                'target_id'   => $employee->id,
                'new_values'  => ['permissions' => $data['permissions']],
            ]);
        }

        // Add roles and permissions to response
        $employee->roles = $employee->roles->pluck('name', 'id');
        $employee->permissions = $employee->getPermissionNames();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    // PUT /employees/{id}
    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        // Use User model for employee data
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);

        $data = $request->validate([
            'first_name' => ['nullable','string','max:120'],
            'last_name'  => ['nullable','string','max:120'],
            'email'      => ['nullable','email','max:255', Rule::unique('users','email')->ignore($employee->id)],
            'phone'      => ['nullable','string','max:50'],
            'password'   => ['nullable','string','min:6'],
            'active'     => ['boolean'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer','exists:api_roles,id'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $old = $employee->only(['first_name','last_name','email','phone','active']);

        $employee->fill(array_filter([
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'email'      => $data['email'] ?? null,
            'phone'      => $data['phone'] ?? null,
            'active'     => array_key_exists('active',$data) ? $data['active'] : null,
        ], fn($v) => !is_null($v)));

        if (!empty($data['password'])) {
            $employee->password = Hash::make($data['password']);
        }

        $employee->save();

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        // Handle role updates
        if (array_key_exists('role_ids', $data)) {
            $oldRoles = $employee->roles->pluck('id')->toArray();
            
            // Ensure roles belong to same tenant
            $availableRoles = Role::where('team_id', $tenantId)->whereIn('id', $data['role_ids'] ?? [])->get();
            $employee->syncRoles($availableRoles);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'role.updated',
                'target_type' => 'users',
                'target_id'   => $employee->id,
                'old_values'  => ['roles' => $oldRoles],
                'new_values'  => ['roles' => $availableRoles->pluck('id')->toArray()],
            ]);
        }

        // Handle permission updates
        if (array_key_exists('permissions', $data)) {
            $oldPermissions = $employee->getPermissionNames()->toArray();
            $employee->syncPermissions($data['permissions']);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'permissions.updated',
                'target_type' => 'users',
                'target_id'   => $employee->id,
                'old_values'  => ['permissions' => $oldPermissions],
                'new_values'  => ['permissions' => $data['permissions']],
            ]);
        }

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'employee.updated',
            'target_type' => 'users',
            'target_id'   => $employee->id,
            'old_values'  => $old,
            'new_values'  => $employee->only(['first_name','last_name','email','phone','active']),
        ]);

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        
        // Add roles and permissions to response
        $employee->roles = $employee->roles->pluck('name', 'id');
        $employee->permissions = $employee->getPermissionNames();

        return response()->json([
            'status' => 'success',
            'data' => $employee
        ]);
    }

    // DELETE /employees/{id}
    public function destroy($id)
    {
        $tenantId = $this->tenantId();
        
        // Use User model for employee data
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        
        // Remove all roles and permissions
        $employee->syncRoles([]);
        $employee->syncPermissions([]);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'employee.deleted',
            'target_type' => 'users',
            'target_id'   => $employee->id,
            'old_values'  => $employee->toArray(),
        ]);

        $employee->delete();

        return response()->noContent();
    }

    // POST /employees/{id}/roles
    public function syncRoles(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        $request->validate([
            'role_ids'   => ['required','array'],
            'role_ids.*' => ['integer','exists:api_roles,id']
        ]);

        // Use User model for employee data
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);
        
        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        
        $oldRoles = $employee->roles->pluck('id')->toArray();
        
        // Ensure roles belong to same tenant
        $availableRoles = Role::where('team_id', $tenantId)->whereIn('id', $request->role_ids)->get();
        $employee->syncRoles($availableRoles);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.assigned',
            'target_type' => 'users',
            'target_id'   => $employee->id,
            'old_values'  => ['roles' => $oldRoles],
            'new_values'  => ['roles' => $availableRoles->pluck('id')->toArray()],
        ]);

        return response()->json([
            'status' => 'success',
            'employee_id' => $employee->id,
            'roles' => $employee->roles->pluck('name', 'id'),
        ]);
    }

    // GET /employees/available-roles
    public function availableRoles()
    {
        $tenantId = $this->tenantId();
        
        // Get roles for the current tenant using api_roles
        $roles = Role::where('team_id', $tenantId)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    // GET /employees/available-permissions
    public function availablePermissions()
    {
        // Get all permissions available in the system
        $permissions = Permission::all()->pluck('name')->sort()->values();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

}
