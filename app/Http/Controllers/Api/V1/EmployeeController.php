<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Api\Role;
use App\Models\Api\Employee;
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

        $q = Employee::with('roles:id,name')
            ->where('user_id', $tenantId)
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

        return response()->json($q->paginate((int)($request->per_page ?? 20)));
    }

    // POST /employees
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'first_name' => ['nullable','string','max:120'],
            'last_name'  => ['nullable','string','max:120'],
            'email'      => ['required','email','max:255', Rule::unique('api_employees','email')],
            'phone'      => ['nullable','string','max:50'],
            'password'   => ['required','string','min:6'],
            'active'     => ['boolean'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer','exists:api_roles,id'],
        ]);

        $employee = new Employee();
        $employee->fill([
            'user_id'    => $tenantId,
            'first_name' => $data['first_name'] ?? null,
            'last_name'  => $data['last_name'] ?? null,
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'active'     => $data['active'] ?? true,
        ]);
        $employee->save();

        if (!empty($data['role_ids'])) {
            // ensure roles belong to same tenant
            $roleIds = Role::where('user_id', $tenantId)->whereIn('id', $data['role_ids'])->pluck('id')->all();
            $employee->roles()->sync($roleIds);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'role.assigned',
                'target_type' => 'api_employees',
                'target_id'   => $employee->id,
                'new_values'  => ['roles' => $roleIds],
            ]);
        }

        return response()->json(
            $employee->load('roles:id,name'),
            201
        );
    }

    // PUT /employees/{id}
    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        $employee = Employee::where('user_id',$tenantId)->findOrFail($id);

        $data = $request->validate([
            'first_name' => ['nullable','string','max:120'],
            'last_name'  => ['nullable','string','max:120'],
            'email'      => ['nullable','email','max:255', Rule::unique('api_employees','email')->ignore($employee->id)],
            'phone'      => ['nullable','string','max:50'],
            'password'   => ['nullable','string','min:6'],
            'active'     => ['boolean'],
            'role_ids'   => ['array'],
            'role_ids.*' => ['integer','exists:api_roles,id'],
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

        if (array_key_exists('role_ids', $data)) {
            $oldRoles = $employee->roles()->pluck('id')->all();
            $roleIds = Role::where('user_id', $tenantId)->whereIn('id', $data['role_ids'] ?? [])->pluck('id')->all();
            $employee->roles()->sync($roleIds);

            ActivityLogger::log([
                'user_id'     => $tenantId,
                'actor_type'  => 'user',
                'actor_id'    => auth()->id(),
                'action'      => 'role.updated',
                'target_type' => 'api_employees',
                'target_id'   => $employee->id,
                'old_values'  => ['roles' => $oldRoles],
                'new_values'  => ['roles' => $roleIds],
            ]);
        }

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'employee.updated',
            'target_type' => 'api_employees',
            'target_id'   => $employee->id,
            'old_values'  => $old,
            'new_values'  => $employee->only(['first_name','last_name','email','phone','active']),
        ]);

        return response()->json($employee->load('roles:id,name'));
    }

    // DELETE /employees/{id}
    public function destroy($id)
    {
        $tenantId = $this->tenantId();
        $employee = Employee::where('user_id',$tenantId)->findOrFail($id);

        $employee->roles()->detach();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'employee.deleted',
            'target_type' => 'api_employees',
            'target_id'   => $employee->id,
            'old_values'  => $employee->toArray(),
        ]);

        $employee->delete();

        return response()->noContent();
    }

    // POST /employees/{id}/roles  (alternative endpoint to sync)
    public function syncRoles(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        $request->validate([
            'role_ids'   => ['required','array'],
            'role_ids.*' => ['integer','exists:api_roles,id']
        ]);

        $employee = Employee::where('user_id',$tenantId)->findOrFail($id);
        $oldRoles = $employee->roles()->pluck('id')->all();

        $roleIds = Role::where('user_id',$tenantId)->whereIn('id',$request->role_ids)->pluck('id')->all();
        $employee->roles()->sync($roleIds);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.assigned',
            'target_type' => 'api_employees',
            'target_id'   => $employee->id,
            'old_values'  => ['roles' => $oldRoles],
            'new_values'  => ['roles' => $roleIds],
        ]);

        return response()->json([
            'employee_id' => $employee->id,
            'roles'       => $employee->roles()->select('id','name')->get(),
        ]);
    }
}
