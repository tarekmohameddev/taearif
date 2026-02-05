<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * RequestsController
 * 
 * API endpoints for Customers Hub Requests Center.
 * Implements read-only aggregation from legacy tables.
 * 
 * Routes:
 * - POST /api/v2/customers-hub/requests/list
 * - GET  /api/v2/customers-hub/requests/filter-options
 * - GET  /api/v2/customers-hub/requests/{requestId}
 * - POST /api/v2/customers-hub/requests/{requestId}/complete
 * - POST /api/v2/customers-hub/requests/{requestId}/dismiss
 * - POST /api/v2/customers-hub/requests/bulk-complete
 * - POST /api/v2/customers-hub/requests/bulk-dismiss
 */
class RequestsController extends ApiController
{
    private ActionsAggregatorService $aggregator;

    public function __construct(ActionsAggregatorService $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * POST /api/v2/customers-hub/requests/list
     * 
     * Get paginated list of customer actions with filtering.
     */
    public function list(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tab' => 'nullable|in:inbox,followups,all,completed',
            'types' => 'nullable|array',
            'types.*' => 'string|in:new_inquiry,callback_request,whatsapp_incoming,property_match,follow_up,site_visit',
            'statuses' => 'nullable|array',
            'statuses.*' => 'string|in:pending,in_progress,completed,dismissed',
            'sources' => 'nullable|array',
            'sources.*' => 'string|in:inquiry,manual,whatsapp,import,referral,property_request',
            'priorities' => 'nullable|array',
            'priorities.*' => 'string|in:low,medium,high,urgent',
            'assignees' => 'nullable|array',
            'assignees.*' => 'integer',
            'customer_id' => 'nullable|integer',
            'due_date_bucket' => 'nullable|in:overdue,today,week,no_date',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:createdAt,dueDate,priority,customerName',
            'sort_dir' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $userId = $this->getTenantUserId($request);

        $filters = $validated;
        $limit = $validated['limit'] ?? 50;
        $offset = $validated['offset'] ?? 0;

        // Get list
        $result = $this->aggregator->getList($userId, $filters, $limit, $offset);

        // Get stats
        $stats = $this->aggregator->getStats($userId, $filters);

        return $this->success([
            'actions' => $result['items'],
            'stats' => $stats,
            'pagination' => [
                'total' => $result['total'],
                'limit' => $result['limit'],
                'offset' => $result['offset'],
                'hasMore' => $result['hasMore'],
            ],
        ]);
    }

    /**
     * GET /api/v2/customers-hub/requests/filter-options
     * 
     * Get available filter options for the requests center.
     * Cached for 30 minutes per user.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $cacheKey = "ch:reqs:filter-options:{$userId}";

        $data = Cache::remember($cacheKey, 1800, function () use ($userId) {
            // Action types
            $types = [
                ['id' => 'new_inquiry', 'label' => 'استفسار جديد', 'labelEn' => 'New Inquiry'],
                ['id' => 'callback_request', 'label' => 'طلب اتصال', 'labelEn' => 'Callback Request'],
                ['id' => 'whatsapp_incoming', 'label' => 'رسالة واتساب', 'labelEn' => 'WhatsApp Message'],
                ['id' => 'property_match', 'label' => 'عقار مطابق', 'labelEn' => 'Property Match'],
                ['id' => 'follow_up', 'label' => 'متابعة', 'labelEn' => 'Follow-up'],
                ['id' => 'site_visit', 'label' => 'معاينة', 'labelEn' => 'Site Visit'],
            ];

            // Statuses
            $statuses = [
                ['id' => 'pending', 'label' => 'قيد الانتظار', 'labelEn' => 'Pending'],
                ['id' => 'in_progress', 'label' => 'قيد التنفيذ', 'labelEn' => 'In Progress'],
                ['id' => 'completed', 'label' => 'مكتمل', 'labelEn' => 'Completed'],
                ['id' => 'dismissed', 'label' => 'مرفوض', 'labelEn' => 'Dismissed'],
            ];

            // Priorities
            $priorities = [
                ['id' => 'urgent', 'label' => 'عاجل', 'labelEn' => 'Urgent', 'color' => '#dc3545'],
                ['id' => 'high', 'label' => 'عالي', 'labelEn' => 'High', 'color' => '#fd7e14'],
                ['id' => 'medium', 'label' => 'متوسط', 'labelEn' => 'Medium', 'color' => '#ffc107'],
                ['id' => 'low', 'label' => 'منخفض', 'labelEn' => 'Low', 'color' => '#28a745'],
            ];

            // Sources
            $sources = [
                ['id' => 'inquiry', 'label' => 'استفسار', 'labelEn' => 'Inquiry'],
                ['id' => 'manual', 'label' => 'يدوي', 'labelEn' => 'Manual'],
                ['id' => 'whatsapp', 'label' => 'واتساب', 'labelEn' => 'WhatsApp'],
                ['id' => 'import', 'label' => 'استيراد', 'labelEn' => 'Import'],
                ['id' => 'referral', 'label' => 'إحالة', 'labelEn' => 'Referral'],
                ['id' => 'property_request', 'label' => 'طلب عقار', 'labelEn' => 'Property Request'],
            ];

            // Due date buckets
            $dueDateBuckets = [
                ['id' => 'overdue', 'label' => 'متأخر', 'labelEn' => 'Overdue'],
                ['id' => 'today', 'label' => 'اليوم', 'labelEn' => 'Today'],
                ['id' => 'week', 'label' => 'هذا الأسبوع', 'labelEn' => 'This Week'],
                ['id' => 'no_date', 'label' => 'بدون موعد', 'labelEn' => 'No Date'],
            ];

            // Customer stages (user-defined)
            $stages = UserApiCustomerStage::where('user_id', $userId)
                ->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'stage_name as label', 'color', 'icon']);

            // Customer types (user-defined)
            $customerTypes = UserApiCustomerType::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Customer priorities (user-defined)
            $customerPriorities = UserApiCustomerPriority::where('user_id', $userId)
                ->orderBy('order')
                ->get(['id', 'name as label', 'value', 'icon', 'color']);

            // Employees (assignees)
            $employees = User::where('tenant_id', $userId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->get(['id', 'first_name', 'last_name', 'email'])
                ->map(fn($e) => [
                    'id' => $e->id,
                    'label' => trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? '')),
                    'email' => $e->email,
                ]);

            return [
                'types' => $types,
                'statuses' => $statuses,
                'priorities' => $priorities,
                'sources' => $sources,
                'dueDateBuckets' => $dueDateBuckets,
                'stages' => $stages,
                'customerTypes' => $customerTypes,
                'customerPriorities' => $customerPriorities,
                'employees' => $employees,
            ];
        });

        return $this->success($data);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}
     * 
     * Get single action detail with related actions.
     */
    public function show(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        // Get related actions for the same customer
        $related = $this->aggregator->getRelated($userId, $requestId, [], 5);

        return $this->success([
            'action' => $action,
            'related' => $related['items'],
        ]);
    }

    /**
     * GET /api/v2/customers-hub/requests/{requestId}/stats
     * 
     * Get stats for a specific action's customer.
     */
    public function actionStats(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $action = $this->aggregator->getById($userId, $requestId);

        if (!$action) {
            return $this->error('Action not found', 404);
        }

        if (!$action->customerId) {
            return $this->success([
                'customerStats' => null,
            ]);
        }

        // Get stats for this customer
        $customerStats = $this->aggregator->getStats($userId, [
            'customer_id' => $action->customerId,
        ]);

        return $this->success([
            'customerStats' => $customerStats,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/complete
     * 
     * Mark an action as completed.
     */
    public function complete(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $success = $this->aggregator->completeAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to complete action', 422);
        }

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action completed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/{requestId}/dismiss
     * 
     * Dismiss an action.
     */
    public function dismiss(Request $request, string $requestId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $success = $this->aggregator->dismissAction($userId, $requestId);

        if (!$success) {
            return $this->error('Failed to dismiss action', 422);
        }

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'message' => 'Action dismissed successfully',
            'actionId' => $requestId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-complete
     * 
     * Bulk complete multiple actions.
     */
    public function bulkComplete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actionIds' => 'required|array|min:1|max:100',
            'actionIds.*' => 'string',
        ]);

        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkComplete($userId, $validated['actionIds']);

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions completed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/requests/bulk-dismiss
     * 
     * Bulk dismiss multiple actions.
     */
    public function bulkDismiss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'actionIds' => 'required|array|min:1|max:100',
            'actionIds.*' => 'string',
        ]);

        $userId = $this->getTenantUserId($request);

        $results = $this->aggregator->bulkDismiss($userId, $validated['actionIds']);

        // Invalidate filter options cache
        $this->invalidateFilterOptionsCache($userId);

        return $this->success([
            'success' => $results['success'],
            'failed' => $results['failed'],
            'message' => sprintf(
                '%d actions dismissed, %d failed',
                count($results['success']),
                count($results['failed'])
            ),
        ]);
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }

    /**
     * Invalidate filter options cache for a user.
     */
    private function invalidateFilterOptionsCache(int $userId): void
    {
        Cache::forget("ch:reqs:filter-options:{$userId}");
    }
}
