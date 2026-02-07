<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
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
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active_only' => 'nullable|in:true,false,1,0',
            'order_by' => 'nullable|string|in:order,created_at',
        ]);

        $activeOnly = in_array(strtolower((string) ($validated['active_only'] ?? '')), ['true', '1'], true);
        $orderBy = $validated['order_by'] ?? 'order';

        $result = $this->stagesService->getAll($activeOnly, $orderBy);

        return $this->successWithSpec(
            $result,
            'Stages retrieved successfully',
            200
        );
    }

    /**
     * POST /api/v2/customers-hub/stages
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stage_id' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:customers_hub_stages,stage_id',
            ],
            'stage_name_ar' => 'required|string|max:255',
            'stage_name_en' => 'required|string|max:255',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'order' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            $stage = $this->stagesService->create($validated);
        } catch (ValidationException $e) {
            return $this->errorWithSpec(
                'Stage ID already exists',
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
            'description' => $stage->description,
            'is_active' => $stage->is_active,
            'created_at' => $stage->created_at?->toIso8601String(),
            'updated_at' => $stage->updated_at?->toIso8601String(),
        ];

        return $this->successWithSpec($data, 'Stage created successfully', 201);
    }

    /**
     * PUT /api/v2/customers-hub/stages/{stage_id}
     */
    public function update(Request $request, string $stageId): JsonResponse
    {
        $validated = $request->validate([
            'stage_name_ar' => 'nullable|string|max:255',
            'stage_name_en' => 'nullable|string|max:255',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'order' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $stage = $this->stagesService->update($stageId, $validated);
        } catch (ModelNotFoundException $e) {
            return $this->errorWithSpec("Stage not found: '{$stageId}'", 404);
        }

        $data = [
            'id' => $stage->id,
            'stage_id' => $stage->stage_id,
            'stage_name_ar' => $stage->stage_name_ar,
            'stage_name_en' => $stage->stage_name_en,
            'color' => $stage->color,
            'order' => $stage->order,
            'description' => $stage->description,
            'is_active' => $stage->is_active,
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
        try {
            $this->stagesService->delete($stageId);
        } catch (ModelNotFoundException $e) {
            return $this->errorWithSpec("Stage not found: '{$stageId}'", 404);
        } catch (StageInUseException $e) {
            return $this->errorWithSpec(
                $e->getMessage(),
                409,
                ['requests_count' => $e->requestsCount]
            );
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
