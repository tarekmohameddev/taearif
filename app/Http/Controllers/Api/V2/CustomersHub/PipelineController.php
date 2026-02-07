<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\PipelineService;
use App\Models\ApiCustomer;

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
     * Accepts requestId (primary) or customerId for backward compatibility (customerId treated as request id).
     */
    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requestId' => 'nullable|integer',
            'customerId' => 'nullable|integer',
            'newStageId' => ['required', 'integer', \Illuminate\Validation\Rule::exists('property_request_statuses', 'id')->where('is_active', true)],
            'notes' => 'nullable|string|max:500',
        ]);

        $requestId = isset($validated['requestId']) ? (int) $validated['requestId'] : (isset($validated['customerId']) ? (int) $validated['customerId'] : null);
        if ($requestId === null) {
            return $this->error('Validation failed', 422, [
                'requestId' => ['The request id field is required when customer id is not present.'],
            ]);
        }

        $userId = $this->getTenantUserId($request);
        $newStatusId = (int) $validated['newStageId'];

        $existing = DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->first(['id', 'full_name', 'status_id']);

        if (!$existing) {
            return $this->error('Request not found', 404);
        }

        $previousStage = $this->pipelineService->getRequestCurrentStatus($userId, $requestId);
        $newStage = $this->pipelineService->getStatusById($newStatusId);
        if (!$newStage) {
            return $this->error('Invalid stage', 422, [
                'newStageId' => ['The specified stage does not exist or is not active.'],
            ]);
        }

        $success = $this->pipelineService->moveRequestToStage($userId, $requestId, $newStatusId);
        if (!$success) {
            return $this->error('Failed to move request', 422);
        }

        $customerId = ApiCustomer::where('property_request_id', $requestId)
            ->where('user_id', $userId)
            ->value('id');

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
            'requestId' => $requestId,
            'customerId' => $customerId,
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
     * Accepts requestIds (primary) or customerIds for backward compatibility (both are request IDs).
     */
    public function bulkMove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requestIds' => 'nullable|array',
            'requestIds.*' => 'integer',
            'customerIds' => 'nullable|array',
            'customerIds.*' => 'integer',
            'newStageId' => ['required', 'integer', \Illuminate\Validation\Rule::exists('property_request_statuses', 'id')->where('is_active', true)],
        ]);

        $requestIds = ! empty($validated['requestIds'])
            ? array_map('intval', $validated['requestIds'])
            : array_map('intval', $validated['customerIds'] ?? []);

        if (empty($requestIds)) {
            return $this->error('Validation failed', 422, [
                'requestIds' => ['At least one of request ids or customer ids is required.'],
            ]);
        }

        $userId = $this->getTenantUserId($request);
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
