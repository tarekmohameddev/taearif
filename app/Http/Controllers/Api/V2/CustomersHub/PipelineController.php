<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\PipelineService;
use App\Models\ApiCustomer;
use App\Models\Api\UserPropertyRequest;

/**
 * PipelineController
 *
 * API endpoints for Customers Hub Pipeline/Kanban board.
 * Board and move operations work with property requests and inquiries,
 * grouped by customers_hub_stages (stage_id).
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
            'filters.stage_id' => 'nullable|array',
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
     * Move a property request or inquiry to a new stage (customers_hub_stages).
     * Accepts requestId, customerId (as request id), or inquiryId. newStageId can be string (stage_id) or integer (id).
     */
    public function move(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requestId' => 'nullable|integer',
            'customerId' => 'nullable|integer',
            'inquiryId' => 'nullable|integer',
            'newStageId' => 'required',
            'notes' => 'nullable|string|max:500',
        ]);

        $requestId = isset($validated['requestId']) ? (int) $validated['requestId'] : (isset($validated['customerId']) ? (int) $validated['customerId'] : null);
        $inquiryId = isset($validated['inquiryId']) ? (int) $validated['inquiryId'] : null;

        if ($requestId === null && $inquiryId === null) {
            return $this->error('Validation failed', 422, [
                'requestId' => ['At least one of requestId or inquiryId is required.'],
            ]);
        }

        if ($requestId !== null && $inquiryId !== null) {
            return $this->error('Validation failed', 422, [
                'requestId' => ['Provide either requestId or inquiryId, not both.'],
            ]);
        }

        $userId = $this->getTenantUserId($request);
        $stageIdString = $this->pipelineService->resolveNewStageId($validated['newStageId']);
        if ($stageIdString === null) {
            return $this->error('Invalid stage', 422, [
                'newStageId' => ['The specified stage does not exist or is not active.'],
            ]);
        }

        $newStage = $this->pipelineService->getStageByStageIdOrId($validated['newStageId']);
        if (!$newStage) {
            return $this->error('Invalid stage', 422, [
                'newStageId' => ['The specified stage does not exist or is not active.'],
            ]);
        }

        $user = $request->user();
        $movedBy = [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
        ];
        if (empty($movedBy['name'])) {
            $movedBy['name'] = $user->email ?? (string) $user->id;
        }

        $newStagePayload = [
            'id' => $newStage->id,
            'stage_id' => $newStage->stage_id,
            'nameAr' => $newStage->name_ar,
            'nameEn' => $newStage->name_en ?? $newStage->name_ar,
        ];

        if ($requestId !== null) {
            return $this->moveRequest($request, $userId, $requestId, $stageIdString, $newStagePayload, $movedBy, $validated['notes'] ?? null);
        }

        return $this->moveInquiry($request, $userId, $inquiryId, $stageIdString, $newStagePayload, $movedBy, $validated['notes'] ?? null);
    }

    private function moveRequest(Request $request, int $userId, int $requestId, string $stageIdString, array $newStagePayload, array $movedBy, ?string $notes): JsonResponse
    {
        $existing = DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->first(['id', 'full_name']);

        if (!$existing) {
            return $this->error('Request not found', 404);
        }

        $previousStage = $this->pipelineService->getRequestCurrentStatus($userId, $requestId);
        $success = $this->pipelineService->moveRequestToStage($userId, $requestId, $stageIdString);
        if (!$success) {
            return $this->error('Failed to move request', 422);
        }

        $customerId = UserPropertyRequest::find($requestId)
            ?->customers()
            ->where('api_customers.user_id', $userId)
            ->value('api_customers.id');

        return $this->success([
            'message' => 'Request moved successfully',
            'source' => 'request',
            'requestId' => $requestId,
            'inquiryId' => null,
            'customerId' => $customerId,
            'customerName' => $existing->full_name ?? '',
            'previousStage' => $previousStage ? [
                'id' => $previousStage->id,
                'stage_id' => $previousStage->stage_id,
                'nameAr' => $previousStage->name_ar,
                'nameEn' => $previousStage->name_en ?? $previousStage->name_ar,
            ] : null,
            'newStage' => $newStagePayload,
            'movedAt' => now()->toIso8601String(),
            'movedBy' => $movedBy,
            'notes' => $notes,
        ]);
    }

    private function moveInquiry(Request $request, int $userId, int $inquiryId, string $stageIdString, array $newStagePayload, array $movedBy, ?string $notes): JsonResponse
    {
        $inquiry = DB::table('api_customer_inquiry as aci')
            ->leftJoin('api_customers as ac', 'aci.customer_id', '=', 'ac.id')
            ->where('aci.id', $inquiryId)
            ->where('aci.user_id', $userId)
            ->select('aci.id', 'aci.customer_id', 'ac.name as customer_name')
            ->first();

        if (!$inquiry) {
            return $this->error('Inquiry not found', 404);
        }

        $previousStage = $this->pipelineService->getInquiryCurrentStage($userId, $inquiryId);
        $success = $this->pipelineService->moveInquiryToStage($userId, $inquiryId, $stageIdString);
        if (!$success) {
            return $this->error('Failed to move inquiry', 422);
        }

        return $this->success([
            'message' => 'Inquiry moved successfully',
            'source' => 'inquiry',
            'requestId' => null,
            'inquiryId' => $inquiryId,
            'customerId' => $inquiry->customer_id,
            'customerName' => $inquiry->customer_name ?? '',
            'previousStage' => $previousStage ? [
                'id' => $previousStage->id,
                'stage_id' => $previousStage->stage_id,
                'nameAr' => $previousStage->name_ar,
                'nameEn' => $previousStage->name_en ?? $previousStage->name_ar,
            ] : null,
            'newStage' => $newStagePayload,
            'movedAt' => now()->toIso8601String(),
            'movedBy' => $movedBy,
            'notes' => $notes,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/pipeline/bulk-move
     *
     * Bulk move property requests to a new stage (customers_hub_stages).
     * Accepts requestIds or customerIds (both are request IDs). newStageId can be string or integer.
     */
    public function bulkMove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requestIds' => 'nullable|array',
            'requestIds.*' => 'integer',
            'customerIds' => 'nullable|array',
            'customerIds.*' => 'integer',
            'newStageId' => 'required',
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
        $stageIdString = $this->pipelineService->resolveNewStageId($validated['newStageId']);
        if ($stageIdString === null) {
            return $this->error('Invalid stage', 422, [
                'newStageId' => ['The specified stage does not exist or is not active.'],
            ]);
        }

        $updated = $this->pipelineService->bulkMoveToStage($userId, $requestIds, $stageIdString);

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
