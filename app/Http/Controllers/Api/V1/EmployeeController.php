<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreEmployeeRequest;
use App\Http\Requests\Api\V1\UpdateEmployeeRequest;
use App\Http\Requests\Api\V1\SyncEmployeeRolesRequest;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Domain\CustomersHub\Services\AssignmentService;
use Illuminate\Support\Facades\Log;


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

        $assignmentService = app(AssignmentService::class);
        $workloadByEmployeeId = collect($assignmentService->getEmployees($tenantId))
            ->mapWithKeys(function (array $row) {
                $id = isset($row['id']) ? (int) $row['id'] : null;
                if (!$id) {
                    return [];
                }
                return [$id => $row];
            });

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
        $employees->getCollection()->transform(function ($employee) use ($tenantId, $workloadByEmployeeId) {
            // Set tenant context for Spatie
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            $employee->roles = $employee->roles->pluck('name', 'id');
            $employee->permissions = $employee->getPermissionNames();

            $workload = $workloadByEmployeeId->get((int) $employee->id) ?? [];
            $employee->customerCount = $workload['customerCount'] ?? 0;
            $employee->activeCount = $workload['activeCount'] ?? 0;
            $employee->loadPercentage = $workload['loadPercentage'] ?? 0;
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
    public function store(StoreEmployeeRequest $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validated();

        // Check employee quota before creating
        $tenant = User::findOrFail($tenantId);
        
        if ($tenant->employee_usage >= $tenant->employee_quota) {
            return response()->json([
                'status'  => 'error',
                'message' => 'لقد وصلت للحد الأقصى لعدد الموظفين المسموح بهم. يرجى شراء إضافة لزيادة الحد.',
                'quota' => $tenant->employee_quota,
                'usage' => $tenant->employee_usage,
                'is_over_limit' => true,
            ], 422);
        }

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
            'onboarding_completed' => true,
        ]);

        // Copy BasicSetting from Tenant
        $tenantBasicSetting = \App\Models\User\BasicSetting::where('user_id', $tenantId)->first();
        if ($tenantBasicSetting) {
            $newBasicSetting = $tenantBasicSetting->replicate();
            $newBasicSetting->user_id = $employee->id;
            $newBasicSetting->save();
        }

        // Copy UserStep from Tenant
        $tenantUserStep = \App\Models\UserStep::where('user_id', $tenantId)->first();
        if ($tenantUserStep) {
            $newUserStep = $tenantUserStep->replicate();
            $newUserStep->user_id = $employee->id;
            $newUserStep->save();
        }

        // Copy GeneralSetting from Tenant
        $tenantGeneralSetting = \App\Models\Api\GeneralSetting::where('user_id', $tenantId)->first();
        if ($tenantGeneralSetting) {
            $newGeneralSetting = $tenantGeneralSetting->replicate();
            $newGeneralSetting->user_id = $employee->id;
            $newGeneralSetting->save();
        }

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

        $savedAssignmentRulesMeta = null;
        if (!empty($data['employeeRules'])) {
            try {
                $savedAssignmentRulesMeta = $this->saveEmployeeAssignmentRules(
                    $tenantId,
                    (int) $employee->id,
                    $data['employeeRules']
                );

                ActivityLogger::log([
                    'user_id'     => $tenantId,
                    'actor_type'  => 'user',
                    'actor_id'    => auth()->id(),
                    'action'      => 'customers_hub.assignment_rules.saved',
                    'target_type' => 'users',
                    'target_id'   => $employee->id,
                    'new_values'  => ['employeeRules' => $savedAssignmentRulesMeta['rules'] ?? []],
                ]);
            } catch (\Throwable $e) {
                Log::error('Employee store: assignment rules save failed', [
                    'tenant_id'   => $tenantId,
                    'employee_id' => $employee->id,
                    'error'       => $e->getMessage(),
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Employee created but assignment rules could not be saved.',
                ], 500);
            }
        }

        // Add roles and permissions to response
        $employee->roles = $employee->roles->pluck('name', 'id');
        $employee->permissions = $employee->getPermissionNames();

        if ($savedAssignmentRulesMeta !== null) {
            $employee->assignment_rules = $savedAssignmentRulesMeta['rules'] ?? [];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    // PUT /employees/{id}
    public function update(UpdateEmployeeRequest $request, $id)
    {
        $tenantId = $this->tenantId();

        // Use User model for employee data
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);

        $data = $request->validated();

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

        $savedAssignmentRulesMeta = null;
        if (array_key_exists('employeeRules', $data) && !empty($data['employeeRules'])) {
            try {
                $savedAssignmentRulesMeta = $this->saveEmployeeAssignmentRules(
                    $tenantId,
                    (int) $employee->id,
                    $data['employeeRules']
                );

                ActivityLogger::log([
                    'user_id'     => $tenantId,
                    'actor_type'  => 'user',
                    'actor_id'    => auth()->id(),
                    'action'      => 'customers_hub.assignment_rules.saved',
                    'target_type' => 'users',
                    'target_id'   => $employee->id,
                    'new_values'  => ['employeeRules' => $savedAssignmentRulesMeta['rules'] ?? []],
                ]);
            } catch (\Throwable $e) {
                Log::error('Employee update: assignment rules save failed', [
                    'tenant_id'   => $tenantId,
                    'employee_id' => $employee->id,
                    'error'       => $e->getMessage(),
                ]);

                return response()->json([
                    'status'  => 'error',
                    'message' => 'Employee updated but assignment rules could not be saved.',
                ], 500);
            }
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

        if ($savedAssignmentRulesMeta !== null) {
            $employee->assignment_rules = $savedAssignmentRulesMeta['rules'] ?? [];
        }

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
    public function syncRoles(SyncEmployeeRolesRequest $request, $id)
    {
        $tenantId = $this->tenantId();
        $validated = $request->validated();

        // Use User model for employee data
        $employee = User::where('tenant_id', $tenantId)
            ->where('account_type', 'employee')
            ->findOrFail($id);

        // Set tenant context for Spatie
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $oldRoles = $employee->roles->pluck('id')->toArray();

        // Ensure roles belong to same tenant
        $availableRoles = Role::where('team_id', $tenantId)->whereIn('id', $validated['role_ids'])->get();
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

        // Get roles for the current tenant with their permissions
        $roles = Role::where('team_id', $tenantId)
            ->with('permissions:id,name')
            ->select('id', 'name')
            ->get();

        // Add permissions_list to each role
        $roles->transform(function ($role) {
            $role->permissions_list = $role->permissions->pluck('name')->toArray();
            return $role;
        });

        return response()->json([
            'status' => 'success',
            'data' => $roles
        ]);
    }

    // GET /employees/available-permissions
    public function availablePermissions()
    {
        // Get all permissions available in the system with id, name, name_ar, and name_en
        $permissions = Permission::all(['id', 'name', 'name_ar', 'name_en'])
            ->sortBy('name')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $permissions
        ]);
    }

    /**
     * Persist Customers Hub auto-assignment rules for one employee (same storage as POST /v2/customers-hub/assignment/rules).
     *
     * @param  array<int, array{isActive: bool, rules: array, employeeId?: string}>  $blocks
     * @return array{savedCount: int, rules: array<int, array{employeeId: string, isActive: bool, rules: array}>}
     */
    private function saveEmployeeAssignmentRules(int $tenantId, int $employeeId, array $blocks): array
    {
        $assignmentService = app(AssignmentService::class);
        $payload = [];
        foreach ($blocks as $block) {
            $payload[] = [
                'employeeId' => (string) $employeeId,
                'isActive'   => $block['isActive'],
                'rules'      => $block['rules'] ?? [],
            ];
        }

        return $assignmentService->saveRules($tenantId, $payload);
    }

}
