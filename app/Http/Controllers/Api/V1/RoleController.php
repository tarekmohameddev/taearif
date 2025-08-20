<?php
namespace App\Http\Controllers\Api\V1;

use App\Models\Api\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;


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
        $q = Role::where('user_id',$tenantId)
            ->when($request->filled('q'), function($qb) use ($request){
                $s = trim($request->q);
                $qb->where('name','like',"%$s%");
            })
            ->orderBy('name');

        return response()->json($q->get(['id','name','permissions']));
    }

    // POST /roles
    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name'        => ['required','string','max:120', Rule::unique('api_roles')->where(fn($q)=>$q->where('user_id',$tenantId))],
            'permissions' => ['array'],
            'permissions.*' => ['string','max:100'],
        ]);

        $role = Role::create([
            'user_id' => $tenantId,
            'name' => $data['name'],
            'permissions' => $data['permissions'] ?? [],
        ]);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.created',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'new_values'  => $role->toArray(),
        ]);

        return response()->json($role, 201);
    }

    // PUT /roles/{id}
    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId();
        $role = Role::where('user_id',$tenantId)->findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes','required','string','max:120', Rule::unique('api_roles')->where(fn($q)=>$q->where('user_id',$tenantId))->ignore($role->id)],
            'permissions' => ['sometimes','array'],
            'permissions.*' => ['string','max:100'],
        ]);

        $old = $role->toArray();

        if (array_key_exists('name',$data)) $role->name = $data['name'];
        if (array_key_exists('permissions',$data)) $role->permissions = $data['permissions'];
        $role->save();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.updated',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'old_values'  => $old,
            'new_values'  => $role->toArray(),
        ]);

        return response()->json($role);
    }

    // DELETE /roles/{id}
    public function destroy($id)
    {
        $tenantId = $this->tenantId();
        $role = Role::where('user_id',$tenantId)->findOrFail($id);

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => 'user',
            'actor_id'    => auth()->id(),
            'action'      => 'role.deleted',
            'target_type' => 'api_roles',
            'target_id'   => $role->id,
            'old_values'  => $role->toArray(),
        ]);

        // Detach from employees implicitly via FK or manually:
        $role->employees()->detach();
        $role->delete();

        return response()->noContent();
    }
}
