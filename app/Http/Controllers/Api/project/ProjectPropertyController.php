<?php

namespace App\Http\Controllers\Api\project;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Project\Properties\AttachProjectPropertiesRequest;
use App\Http\Requests\Api\Project\Properties\ListProjectPropertiesRequest;
use App\Http\Requests\Api\Project\Properties\StoreProjectPropertyRequest;
use App\Http\Requests\Api\Project\Properties\UpdateProjectPropertyRequest;
use App\Http\Resources\Api\ProjectPropertyResource;
use App\Models\User;
use App\Services\Project\ProjectPropertyService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProjectPropertyController extends Controller
{
    public function __construct(
        private readonly ProjectPropertyService $projectPropertyService,
    ) {
    }

    public function index(ListProjectPropertiesRequest $request, int $project): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $perPage = max(1, min((int) $request->query('per_page', 25), 100));
            $properties = $this->projectPropertyService->listForProject($owner->id, $project, $perPage, $request->only([
                'unit_status', 'listing_purpose', 'publish_status', 'category_id', 'property_type',
                'price_from', 'price_to', 'floor_number', 'city_id', 'state_id',
                'payment_method', 'search',
            ]));

            return response()->json([
                'status' => 'success',
                'data' => [
                    'properties' => ProjectPropertyResource::collection($properties->items()),
                    'pagination' => [
                        'current_page' => $properties->currentPage(),
                        'per_page' => $properties->perPage(),
                        'total' => $properties->total(),
                        'last_page' => $properties->lastPage(),
                    ],
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage() ?: 'Project not found for this tenant.');
        }
    }

    public function store(StoreProjectPropertyRequest $request, int $project): JsonResponse
    {
        try {
            $user = auth()->user();
            $owner = $this->resolveTenantOwner();
            $property = $this->projectPropertyService->createForProject(
                $owner->id,
                $project,
                $request->validated(),
                $user->id,
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Property created successfully',
                'data' => [
                    'property' => new ProjectPropertyResource($property),
                ],
            ], 201);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage() ?: 'Project not found for this tenant.');
        } catch (AccessDeniedHttpException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage(),
            ], 403);
        } catch (HttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    public function attach(AttachProjectPropertiesRequest $request, int $project): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $properties = $this->projectPropertyService->attachMany(
                $owner->id,
                $project,
                $request->validated('property_ids'),
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'properties' => ProjectPropertyResource::collection($properties),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage() ?: 'Project or property not found for this tenant.');
        } catch (ConflictHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    public function update(UpdateProjectPropertyRequest $request, int $project, int $property): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $updatedProperty = $this->projectPropertyService->updateForProject(
                $owner->id,
                $project,
                $property,
                $request->validated(),
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Property updated successfully',
                'data' => [
                    'property' => new ProjectPropertyResource($updatedProperty),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage() ?: 'Project or property not found for this tenant.');
        }
    }

    public function destroy(Request $request, int $project, int $property): JsonResponse
    {
        try {
            $owner = $this->resolveTenantOwner();
            $hardDelete = filter_var($request->query('hard_delete', false), FILTER_VALIDATE_BOOLEAN);

            $this->projectPropertyService->detachFromProject(
                $owner->id,
                $project,
                $property,
                $hardDelete,
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'detached' => !$hardDelete,
                    'deleted' => $hardDelete,
                    'property_id' => $property,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage() ?: 'Project or property not found for this tenant.');
        } catch (ConflictHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 409);
        } catch (HttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        }
    }

    private function resolveTenantOwner(): User
    {
        $user = auth()->user();

        return method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
    }

    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], 404);
    }
}
