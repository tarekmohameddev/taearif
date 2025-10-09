<?php

namespace App\Http\Controllers\Api\Rbac;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;


class PermissionAdminController extends Controller
{
    private function tenantId(Request $request): int
    {
        $u = $request->user();
        return $u->isTenant() ? (int) $u->id : (int) $u->tenant_id;
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $available = Permission::query()
            ->where(fn($q) => $q->whereNull('team_id')->orWhere('team_id', $tenantId))
            ->orderBy('name')
            ->get(['id','name','name_ar','name_en','team_id','guard_name','created_at','updated_at']);

        return response()->json([
            'status' => 'success',
            'data' => ['permissions' => $available],
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        $input = $request->input('name');

        if (is_array($input)) {
            $data = $request->validate([
                'name' => ['array','min:1'],
                'name.*' => ['string','max:191','distinct:strict'],
            ]);

            $names = collect($data['name'])
                ->map(fn ($n) => trim($n))
                ->filter()
                ->unique()
                ->values();

            $existing = Permission::query()
                ->where('team_id', $tenantId)
                ->whereIn('name', $names)
                ->pluck('name')
                ->values();

            $toCreate = $names->diff($existing)->values();

            $created = $toCreate->map(function ($name) use ($tenantId) {
                $p = Permission::create([
                    'name'       => $name,
                    'guard_name' => 'sanctum',
                    'team_id'    => $tenantId,
                ]);
                return ['id' => $p->id, 'name' => $p->name, 'team_id' => $p->team_id];
            })->all();

        return response()->json([
                'status' => 'success',
                'data'   => [
                    'created' => $created,
                    'skipped' => $existing, // already existed in this team
                ],
            ], 201);
        }

        $data = $request->validate([
            'name' => [
                'required','string','max:191',
                Rule::unique('api_permissions', 'name')->where(fn ($q) => $q->where('team_id', $tenantId)),
            ],
            'name_ar' => ['nullable','string','max:191'],
            'name_en' => ['nullable','string','max:191'],
        ]);

        $perm = Permission::create([
            'name'       => $data['name'],
            'name_ar'    => $data['name_ar'] ?? null,
            'name_en'    => $data['name_en'] ?? null,
            'guard_name' => 'sanctum',
            'team_id'    => $tenantId,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => ['id' => $perm->id, 'name' => $perm->name, 'name_ar' => $perm->name_ar, 'name_en' => $perm->name_en, 'team_id' => $perm->team_id],
        ], 201);
    }


    public function update(Request $request, Permission $permission)
    {
        $tenantId = $this->tenantId($request);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        if (! is_null($permission->team_id) && (int) $permission->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }
        if (is_null($permission->team_id)) {
            return response()->json(['status' => 'error', 'message' => 'Cannot rename global permission'], 422);
        }

        $data = $request->validate([
            'name' => [
                'required','string','max:191',
                Rule::unique('api_permissions', 'name')
                    ->where(fn($q) => $q->where('team_id', $tenantId))
                    ->ignore($permission->id),
            ],
            'name_ar' => ['nullable','string','max:191'],
            'name_en' => ['nullable','string','max:191'],
        ]);

        $permission->name = $data['name'];
        if (isset($data['name_ar'])) {
            $permission->name_ar = $data['name_ar'];
        }
        if (isset($data['name_en'])) {
            $permission->name_en = $data['name_en'];
        }
        $permission->save();

        return response()->json(['status'=>'success','data'=>['id'=>$permission->id,'name'=>$permission->name,'name_ar'=>$permission->name_ar,'name_en'=>$permission->name_en]]);
    }

    public function destroy(Request $request, Permission $permission)
    {
        $tenantId = $this->tenantId($request);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

        if (is_null($permission->team_id) || (int) $permission->team_id !== $tenantId) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $permission->delete();
        return response()->json(['status'=>'success','message'=>'Permission deleted']);
    }
}
