<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V2\CustomersHub\StagesIndexRequest;
use App\Http\Requests\Api\V2\CustomersHub\StoreStageRequest;
use App\Http\Requests\Api\V2\CustomersHub\UpdateStageRequest;
use App\Domain\CustomersHub\Services\CustomersHubStagesService;
use App\Domain\CustomersHub\Exceptions\StageInUseException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * StagesController
 *
 * API endpoints for Customers Hub dynamic stages.
 *
 * Routes:
 * - GET    /api/v2/customers-hub/stages
 * - POST   /api/v2/customers-hub/stages
 * - PUT    /api/v2/customers-hub/stages/{stage_id}
 * - DELETE /api/v2/customers-hub/stages/{stage_id}
 */
class StagesController extends ApiController
{
    public function __construct(
        private CustomersHubStagesService $stagesService
    ) {}

    /**
     * GET /api/v2/customers-hub/stages
     */
    public function index(StagesIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $activeOnly = in_array(strtolower((string) ($validated['active_only'] ?? '')), ['true', '1'], true);
        $orderBy = $validated['order_by'] ?? 'order';

        $tenantUserId = method_exists($request->user(), 'tenantOwnerId')
            ? (int) $request->user()->tenantOwnerId()
            : (int) $request->user()->id;

        $result = $this->stagesService->getAll($activeOnly, $orderBy, $tenantUserId);

        return $this->successWithSpec(
            $result,
            'Stages retrieved successfully',
            200
        );
    }

    /**
     * POST /api/v2/customers-hub/stages
     */
    public function store(StoreStageRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['tenant_user_id'] = method_exists($request->user(), 'tenantOwnerId')
            ? (int) $request->user()->tenantOwnerId()
            : (int) $request->user()->id;

        try {
            $stage = $this->stagesService->create($validated);
        } catch (ValidationException $e) {
            return $this->errorWithSpec(
                'Stage create failed',
                409
            );
        }

        $data = [
            'id' => $stage->id,
            'stage_id' => $stage->stage_id,
            'stage_name_ar' => $stage->stage_name_ar,
            'stage_name_en' => $stage->stage_name_en,
            'color' => $stage->color,
            'order' => $stage->order,
            // For tenant custom stages, base == effective
            'base_stage_name_ar' => $stage->stage_name_ar,
            'base_stage_name_en' => $stage->stage_name_en,
            'base_color' => $stage->color,
            'base_order' => $stage->order,
            'is_overridden' => false,
            'description' => $stage->description,
            'is_active' => $stage->is_active,
            'is_global' => (bool) ($stage->is_global ?? true),
            'is_system' => (bool) ($stage->is_system ?? false),
            'user_id' => $stage->user_id ?? null,
            'created_at' => $stage->created_at?->toIso8601String(),
            'updated_at' => $stage->updated_at?->toIso8601String(),
        ];

        return $this->successWithSpec($data, 'Stage created successfully', 201);
    }

    /**
     * PUT /api/v2/customers-hub/stages/{stage_id}
     */
    public function update(UpdateStageRequest $request, string $stageId): JsonResponse
    {
        $validated = $request->validated();
        $validated['tenant_user_id'] = method_exists($request->user(), 'tenantOwnerId')
            ? (int) $request->user()->tenantOwnerId()
            : (int) $request->user()->id;

        try {
            $stage = $this->stagesService->update($stageId, $validated);
        } catch (ModelNotFoundException $e) {
            return $this->errorWithSpec("Stage not found: '{$stageId}'", 404);
        } catch (ValidationException $e) {
            return $this->errorWithSpec($e->getMessage(), 422);
        }

        // Return the same shape as index, including base_* and is_overridden.
        $presenter = app(\App\Domain\CustomersHub\Services\CustomersHubStagesPresenter::class);
        $row = $presenter->stagesQueryForTenant((int) $validated['tenant_user_id'], false)
            ->where('s.stage_id', $stageId)
            ->first();

        $data = $row ? [
            'id' => (int) $row->id,
            'stage_id' => (string) $row->stage_id,
            'stage_name_ar' => $row->stage_name_ar,
            'stage_name_en' => $row->stage_name_en,
            'color' => $row->color,
            'order' => (int) $row->order,
            'base_stage_name_ar' => $row->base_stage_name_ar ?? $row->stage_name_ar,
            'base_stage_name_en' => $row->base_stage_name_en ?? $row->stage_name_en,
            'base_color' => $row->base_color ?? $row->color,
            'base_order' => (int) ($row->base_order ?? $row->order),
            'is_overridden' => ($row->override_id ?? null) !== null,
            'description' => $row->description,
            'is_active' => (bool) $row->is_active,
            'is_global' => true,
            'is_system' => (bool) ($row->is_system ?? false),
            'user_id' => $row->user_id ?? null,
            'created_at' => is_string($row->created_at) ? $row->created_at : (string) $row->created_at,
            'updated_at' => is_string($row->updated_at) ? $row->updated_at : (string) $row->updated_at,
        ] : [
            'id' => $stage->id,
            'stage_id' => $stage->stage_id,
            'stage_name_ar' => $stage->stage_name_ar,
            'stage_name_en' => $stage->stage_name_en,
            'color' => $stage->color,
            'order' => $stage->order,
            'base_stage_name_ar' => $stage->stage_name_ar,
            'base_stage_name_en' => $stage->stage_name_en,
            'base_color' => $stage->color,
            'base_order' => $stage->order,
            'is_overridden' => false,
            'description' => $stage->description,
            'is_active' => $stage->is_active,
            'is_global' => (bool) ($stage->is_global ?? true),
            'is_system' => (bool) ($stage->is_system ?? false),
            'user_id' => $stage->user_id ?? null,
            'created_at' => $stage->created_at?->toIso8601String(),
            'updated_at' => $stage->updated_at?->toIso8601String(),
        ];

        return $this->successWithSpec($data, 'Stage updated successfully', 200);
    }

    /**
     * DELETE /api/v2/customers-hub/stages/{stage_id}
     */
    public function destroy(string $stageId): JsonResponse
    {
        $tenantUserId = method_exists(auth()->user(), 'tenantOwnerId')
            ? (int) auth()->user()->tenantOwnerId()
            : (int) auth()->id();

        try {
            $this->stagesService->delete($stageId, $tenantUserId);
        } catch (ModelNotFoundException $e) {
            return $this->errorWithSpec("Stage not found: '{$stageId}'", 404);
        } catch (StageInUseException $e) {
            return $this->errorWithSpec(
                $e->getMessage(),
                409,
                ['requests_count' => $e->requestsCount]
            );
        } catch (\RuntimeException $e) {
            return $this->errorWithSpec($e->getMessage(), 403);
        }

        return $this->successWithSpec(null, 'Stage deleted successfully', 200);
    }

    protected function successWithSpec(mixed $data, string $message, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    protected function errorWithSpec(string $message, int $code = 422, array $data = []): JsonResponse
    {
        $payload = [
            'success' => false,
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'data' => !empty($data) ? $data : null,
            'timestamp' => now()->toIso8601String(),
        ];
        return response()->json($payload, $code);
    }
}
