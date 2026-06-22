<?php

namespace App\Http\Controllers\Api\Audit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Audit\Concerns\RespondsWithEntityAuditLogs;
use App\Models\Building;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Services\Audit\EntityAuditLogQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EntityAuditLogController extends Controller
{
    use RespondsWithEntityAuditLogs;

    public function __construct(
        private readonly EntityAuditLogQueryService $queryService,
    ) {}

    public function forProperty(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $this->canViewEntityAuditLog($user, 'properties.view_audit_log')) {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $property = $this->resolveProperty($id);
        $tenantId = $this->resolveTenantIdForAudit($user);

        $paginator = $this->queryService->paginateForEntity('property', $property->id, $tenantId, $request);

        return $this->respondWithEntityAuditLogs($paginator, $request->boolean('with_actor'));
    }

    public function forProject(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $this->canViewEntityAuditLog($user, 'projects.view_audit_log')) {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $project = $this->resolveProject($id);
        $tenantId = $this->resolveTenantIdForAudit($user);

        $paginator = $this->queryService->paginateForEntity('project', $project->id, $tenantId, $request);

        return $this->respondWithEntityAuditLogs($paginator, $request->boolean('with_actor'));
    }

    public function forBuilding(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        if (! $this->canViewEntityAuditLog($user, 'buildings.view_audit_log')) {
            abort(403, 'Unauthorized to view audit logs.');
        }

        $building = $this->resolveBuilding($id);
        $tenantId = $this->resolveTenantIdForAudit($user);

        $paginator = $this->queryService->paginateForEntity('building', $building->id, $tenantId, $request);

        return $this->respondWithEntityAuditLogs($paginator, $request->boolean('with_actor'));
    }

    private function resolveProperty(int $id): Property
    {
        $user = Auth::user();
        $allowedUserIds = $this->resolveAllowedUserIds($user);

        return Property::where('id', $id)->whereIn('user_id', $allowedUserIds)->firstOrFail();
    }

    private function resolveProject(int $id): Project
    {
        $user = Auth::user();
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        return Project::where('id', $id)->where('user_id', $owner->id)->firstOrFail();
    }

    private function resolveBuilding(int $id): Building
    {
        $user = Auth::user();
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        return Building::where('id', $id)->where('user_id', $owner->id)->firstOrFail();
    }

    /**
     * @return list<int>
     */
    private function resolveAllowedUserIds(\App\Models\User $user): array
    {
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $allowedUserIds = [$owner->id];

        try {
            $employeeIds = \App\Models\User::where('tenant_id', $owner->id)->pluck('id')->toArray();
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {
            // fall back to owner-only scoping
        }

        return array_values($allowedUserIds);
    }
}
