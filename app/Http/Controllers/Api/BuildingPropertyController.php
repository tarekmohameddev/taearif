<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Building\AttachBuildingPropertyRequest;
use App\Http\Resources\Api\BuildingPropertyResource;
use App\Models\User;
use App\Services\Building\BuildingPropertyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuildingPropertyController extends Controller
{
    public function __construct(
        private readonly BuildingPropertyService $buildingPropertyService,
    ) {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $perPage = max(1, min((int) $request->query('per_page', 25), 100));
            $search = $request->query('search');

            $result = $this->buildingPropertyService->listForBuilding(
                $owner->id,
                $id,
                $perPage,
                is_string($search) ? $search : null,
            );

            $properties = $result['properties'];
            $building = $result['building'];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'building' => [
                        'id' => $building->id,
                        'name' => $building->name,
                    ],
                    'properties' => BuildingPropertyResource::collection($properties->items()),
                    'pagination' => [
                        'current_page' => $properties->currentPage(),
                        'per_page' => $properties->perPage(),
                        'total' => $properties->total(),
                        'last_page' => $properties->lastPage(),
                    ],
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Building not found for this tenant.',
            ], 404);
        }
    }

    public function attach(AttachBuildingPropertyRequest $request, int $id): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $property = $this->buildingPropertyService->attach(
                $owner->id,
                $id,
                (int) $request->validated('property_id'),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Property attached to building successfully',
                'data' => [
                    'property' => new BuildingPropertyResource($property),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Building or property not found for this tenant.',
            ], 404);
        }
    }

    private function resolveTenantOwner(): User
    {
        $user = auth()->user();

        return method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
    }
}
