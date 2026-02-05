<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\PipelineService;

/**
 * PipelineController
 * 
 * API endpoints for Customers Hub Pipeline/Kanban board.
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
     * Get pipeline board data with analytics.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'nullable|in:board,analytics',
            'includeAnalytics' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'filters.priority' => 'nullable|array',
            'filters.source' => 'nullable|array',
            'filters.assignedEmployeeId' => 'nullable|integer',
            'filters.search' => 'nullable|string|max:255',
        ]);

        $userId = $this->getTenantUserId($request);
        $action = $validated['action'] ?? 'board';
        $filters = $validated['filters'] ?? [];
        $includeAnalytics = $validated['includeAnalytics'] ?? false;

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
     * Move customer to a new stage.
     */
    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerId' => 'required|integer',
            'newStageId' => 'required|integer',
        ]);

        $userId = $this->getTenantUserId($request);

        $success = $this->pipelineService->moveCustomerToStage(
            $userId,
            $validated['customerId'],
            $validated['newStageId']
        );

        if (!$success) {
            return $this->error('Failed to move customer', 422);
        }

        return $this->success([
            'message' => 'Customer moved successfully',
            'customerId' => $validated['customerId'],
            'newStageId' => $validated['newStageId'],
        ]);
    }

    /**
     * POST /api/v2/customers-hub/pipeline/bulk-move
     * 
     * Bulk move customers to a new stage.
     */
    public function bulkMove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerIds' => 'required|array|min:1',
            'customerIds.*' => 'integer',
            'newStageId' => 'required|integer',
        ]);

        $userId = $this->getTenantUserId($request);

        $updated = $this->pipelineService->bulkMoveToStage(
            $userId,
            $validated['customerIds'],
            $validated['newStageId']
        );

        return $this->success([
            'updated' => $updated,
            'message' => sprintf('%d customers moved successfully', $updated),
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
