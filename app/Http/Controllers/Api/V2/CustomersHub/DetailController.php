<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\CustomerDetailService;
use App\Http\Requests\Api\V2\CustomersHub\UpdateDetailRequest;
use App\Http\Requests\Api\V2\CustomersHub\UpdatePreferencesRequest;
use App\Http\Requests\Api\V2\CustomersHub\AddTaskRequest;
use App\Http\Requests\Api\V2\CustomersHub\UpdateTaskRequest;

/**
 * DetailController
 * 
 * API endpoints for Customer Detail page.
 * 
 * Routes:
 * - GET    /api/v2/customers-hub/customers/{customerId}
 * - PUT    /api/v2/customers-hub/customers/{customerId}
 * - POST   /api/v2/customers-hub/customers/{customerId}/tasks
 * - PUT    /api/v2/customers-hub/customers/{customerId}/tasks/{taskId}
 * - DELETE /api/v2/customers-hub/customers/{customerId}/tasks/{taskId}
 * - PUT    /api/v2/customers-hub/customers/{customerId}/preferences
 */
class DetailController extends ApiController
{
    private CustomerDetailService $detailService;

    public function __construct(CustomerDetailService $detailService)
    {
        $this->detailService = $detailService;
    }

    /**
     * GET /api/v2/customers-hub/customers/{customerId}
     * 
     * Get complete customer details.
     */
    public function show(Request $request, int $customerId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $details = $this->detailService->getCustomerDetails($userId, $customerId);

        if (!$details) {
            return $this->error('Customer not found', 404);
        }

        return $this->success($details);
    }

    /**
     * PUT /api/v2/customers-hub/customers/{customerId}
     * 
     * Update customer information.
     */
    public function update(UpdateDetailRequest $request, int $customerId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $success = $this->detailService->updateCustomer($userId, $customerId, $validated);

        if (!$success) {
            return $this->error('Failed to update customer', 422);
        }

        return $this->success([
            'message' => 'Customer updated successfully',
            'customerId' => $customerId,
        ]);
    }

    /**
     * POST /api/v2/customers-hub/customers/{customerId}/tasks
     * 
     * Add new task for customer.
     */
    public function addTask(AddTaskRequest $request, int $customerId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $taskId = $this->detailService->addTask($userId, $customerId, $validated);

        return $this->success([
            'message' => 'Task added successfully',
            'taskId' => $taskId,
        ], 201);
    }

    /**
     * PUT /api/v2/customers-hub/customers/{customerId}/tasks/{taskId}
     * 
     * Update existing task.
     */
    public function updateTask(UpdateTaskRequest $request, int $customerId, int $taskId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        // Map to database fields
        $data = [];
        if (isset($validated['datetime'])) {
            $data['datetime'] = $validated['datetime'];
        }
        if (isset($validated['notes'])) {
            $data['description'] = $validated['notes'];
        }
        if (isset($validated['status'])) {
            $data['status'] = $validated['status'];
        }
        if (isset($validated['priority'])) {
            $data['priority'] = $validated['priority'];
        }

        $success = $this->detailService->updateTask($userId, $taskId, $data);

        if (!$success) {
            return $this->error('Failed to update task', 422);
        }

        return $this->success([
            'message' => 'Task updated successfully',
            'taskId' => $taskId,
        ]);
    }

    /**
     * DELETE /api/v2/customers-hub/customers/{customerId}/tasks/{taskId}
     * 
     * Delete task.
     */
    public function deleteTask(Request $request, int $customerId, int $taskId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $success = $this->detailService->deleteTask($userId, $taskId);

        if (!$success) {
            return $this->error('Failed to delete task', 422);
        }

        return $this->success([
            'message' => 'Task deleted successfully',
            'taskId' => $taskId,
        ]);
    }

    /**
     * PUT /api/v2/customers-hub/customers/{customerId}/preferences
     * 
     * Update customer preferences/requirements.
     */
    public function updatePreferences(UpdatePreferencesRequest $request, int $customerId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);

        $preferenceId = $this->detailService->updatePreferences($userId, $customerId, $validated);

        return $this->success([
            'message' => 'Preferences updated successfully',
            'preferenceId' => $preferenceId,
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
