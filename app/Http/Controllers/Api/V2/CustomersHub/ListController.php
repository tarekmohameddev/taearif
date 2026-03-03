<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\CustomersListService;
use App\Http\Requests\Api\V2\CustomersHub\ListRequest;
use App\Http\Requests\Api\V2\CustomersHub\BulkListRequest;
use Illuminate\Support\Facades\Cache;

/**
 * ListController
 * 
 * API endpoints for Customers Hub List page.
 * 
 * Routes:
 * - POST /api/v2/customers-hub/list
 * - GET  /api/v2/customers-hub/list/filter-options
 * - POST /api/v2/customers-hub/list/bulk
 */
class ListController extends ApiController
{
    private CustomersListService $listService;

    public function __construct(CustomersListService $listService)
    {
        $this->listService = $listService;
    }

    /**
     * POST /api/v2/customers-hub/list
     * 
     * Get customers list with optional stats.
     */
    public function list(ListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'] ?? 'list';
        $filters = $validated['filters'] ?? [];
        $includeStats = $validated['includeStats'] ?? false;

        $response = [];

        if ($action === 'stats' || $includeStats) {
            $response['stats'] = $this->listService->getStats($userId, $filters);
        }

        if ($action === 'list') {
            $page = $validated['pagination']['page'] ?? 1;
            $limit = $validated['pagination']['limit'] ?? 50;
            
            $result = $this->listService->getList($userId, $filters, $page, $limit);
            $response = array_merge($response, $result);
        }

        return $this->success($response);
    }

    /**
     * GET /api/v2/customers-hub/list/filter-options
     * 
     * Get filter options (cached).
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $cacheKey = "ch:list:filter-options:{$userId}";

        $options = Cache::remember($cacheKey, 1800, function () use ($userId) {
            return $this->listService->getFilterOptions($userId);
        });

        return $this->success($options);
    }

    /**
     * POST /api/v2/customers-hub/list/bulk
     * 
     * Bulk operations on customers.
     */
    public function bulk(BulkListRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'];
        $customerIds = $validated['customerIds'];
        $data = $validated['data'] ?? [];

        $updateData = match ($action) {
            'update_stage' => ['stage_id' => $data['stageId'] ?? null],
            'update_priority' => ['priority_id' => $data['priorityId'] ?? null],
            'update_type' => ['type_id' => $data['typeId'] ?? null],
            'assign_employee' => ['responsible_employee_id' => $data['employeeId'] ?? null],
            'archive' => ['is_archived' => 1],
            'delete' => ['deleted_at' => now()],
            default => [],
        };

        if (empty($updateData)) {
            return $this->error('Invalid bulk action', 422);
        }

        $updated = $this->listService->bulkUpdate($userId, $customerIds, $updateData);

        // Invalidate cache
        Cache::forget("ch:list:filter-options:{$userId}");

        return $this->success([
            'updated' => $updated,
            'message' => sprintf('%d customers updated successfully', $updated),
        ]);
    }

    /**
     * GET /api/v2/customers-hub/list/stats
     * 
     * Get comprehensive customer statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $stats = $this->listService->getStats($userId);
        
        return $this->success([
            'stats' => $stats
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
