<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\PipelineService;

/**
 * PipelineController
 *
 * API endpoints for Customers Hub Pipeline/Kanban board.
 * Board and move operations work with property requests (users_property_requests),
 * grouped by request lifecycle stages (property_request_statuses).
 *
 * Routes:
 * - POST /api/v2/customers-hub/pipeline
 * - POST /api/v2/customers-hub/pipeline/move
 * - POST /api/v2/customers-hub/pipeline/bulk-move
 */
class PipelineController extends ApiController
{
    private PipelineService $pipelineService;

    public function __construct(PipelineService $pipelineService)
    {
        $this->pipelineService = $pipelineService;
    }

    /**
     * POST /api/v2/customers-hub/pipeline
     *
     * Get pipeline board data (request-based) with optional analytics.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'nullable|in:board,analytics,get_board',
            'includeAnalytics' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|array',
            'filters.status.*' => 'integer',
            'filters.status_id' => 'nullable|array',
            'filters.status_id.*' => 'integer',
            'filters.property_type' => 'nullable|array',
            'filters.property_type.*' => 'string',
            'filters.city_id' => 'nullable|integer',
            'filters.district_id' => 'nullable|integer',
            'filters.districts_id' => 'nullable|integer',
            'filters.budget_from' => 'nullable|numeric',
            'filters.budget_to' => 'nullable|numeric',
            'filters.assignedEmployeeId' => 'nullable|integer',
            'filters.search' => 'nullable|string|max:255',
        ]);

        $userId = $this->getTenantUserId($request);
        $action = $validated['action'] ?? 'board';
        $filters = $validated['filters'] ?? [];
        $includeAnalytics = $validated['includeAnalytics'] ?? false;

        if ($action === 'get_board') {
            $action = 'board';
        }

        $response = [];

        if ($action === 'board') {
            $board = $this->pipelineService->getPipelineBoard($userId, $filters);
            $response = array_merge($response, $board);
        }

        if ($action === 'analytics' || $includeAnalytics) {
            $response['analytics'] = $this->pipelineService->getStageAnalytics($userId, $filters);
        }

        return $this->success($response);
    }

    /**
     * POST /api/v2/customers-hub/pipeline/move
     *
     * Move a property request to a new stage (property_request_statuses.id).
     * customerId in body is semantically the requestId.
     */
    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerId' => 'required|integer',
            'newStageId' => ['required', 'integer', \Illuminate\Validation\Rule::exists('property_request_statuses', 'id')->where('is_active', true)],
            'notes' => 'nullable|string|max:500',
        ]);

        $userId = $this->getTenantUserId($request);
        $requestId = (int) $validated['customerId'];
        $newStatusId = (int) $validated['newStageId'];

        $existing = DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->first(['id', 'full_name', 'status_id']);

        if (!$existing) {
            return $this->error('Request not found or not active', 422);
        }

        $previousStage = $this->pipelineService->getRequestCurrentStatus($userId, $requestId);
        $newStage = $this->pipelineService->getStatusById($newStatusId);
        if (!$newStage) {
            return $this->error('Invalid stage', 422);
        }

        $success = $this->pipelineService->moveRequestToStage($userId, $requestId, $newStatusId);
        if (!$success) {
            return $this->error('Failed to move request', 422);
        }

        $user = $request->user();
        $movedBy = [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        ];
        if (empty($movedBy['name'])) {
            $movedBy['name'] = $user->email ?? (string) $user->id;
        }

        return $this->success([
            'message' => 'Request moved successfully',
            'customerId' => $requestId,
            'customerName' => $existing->full_name ?? '',
            'previousStage' => $previousStage ? [
                'id' => $previousStage->id,
                'nameAr' => $previousStage->name_ar,
                'nameEn' => $previousStage->name_en ?? $previousStage->name_ar,
            ] : null,
            'newStage' => [
                'id' => $newStage->id,
                'nameAr' => $newStage->name_ar,
                'nameEn' => $newStage->name_en ?? $newStage->name_ar,
            ],
            'movedAt' => now()->toIso8601String(),
            'movedBy' => $movedBy,
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/pipeline/bulk-move
     *
     * Bulk move property requests to a new stage (property_request_statuses.id).
     * customerIds in body are semantically request IDs.
     */
    public function bulkMove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerIds' => 'required|array|min:1',
            'customerIds.*' => 'integer',
            'newStageId' => ['required', 'integer', \Illuminate\Validation\Rule::exists('property_request_statuses', 'id')->where('is_active', true)],
        ]);

        $userId = $this->getTenantUserId($request);
        $requestIds = array_map('intval', $validated['customerIds']);
        $newStatusId = (int) $validated['newStageId'];

        $updated = $this->pipelineService->bulkMoveToStage($userId, $requestIds, $newStatusId);

        return $this->success([
            'updated' => $updated,
            'message' => sprintf('%d request(s) moved successfully', $updated),
        ]);
    }

    /**
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
