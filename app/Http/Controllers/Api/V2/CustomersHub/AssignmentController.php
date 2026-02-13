<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\AssignmentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AssignmentController
 * 
 * API endpoints for Customers Hub Assignment functionality.
 * 
 * Routes:
 * - GET  /api/v2/customers-hub/assignment/employees
 * - GET  /api/v2/customers-hub/assignment/unassigned-count
 * - POST /api/v2/customers-hub/assignment/auto-assign
 * - POST /api/v2/customers-hub/assignment/assign
 * - POST /api/v2/customers-hub/assignment/rules
 * - GET  /api/v2/customers-hub/assignment/rules
 */
class AssignmentController extends ApiController
{
    private AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * GET /api/v2/customers-hub/assignment/employees
     * 
     * Get employees with their workload statistics.
     */
    public function employees(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        
        // Cache for 30 minutes
        $cacheKey = "ch:assignment:employees:{$userId}";
        $employees = Cache::remember($cacheKey, 1800, function () use ($userId) {
            return $this->assignmentService->getEmployees($userId);
        });

        return $this->successWithSpec(
            ['employees' => $employees],
            'Employees retrieved successfully'
        );
    }

    /**
     * GET /api/v2/customers-hub/assignment/unassigned-count
     * 
     * Get count of unassigned customers.
     */
    public function unassignedCount(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $count = $this->assignmentService->getUnassignedCount($userId);

        return $this->successWithSpec(
            ['unassignedCount' => $count],
            'Unassigned count retrieved successfully'
        );
    }

    /**
     * POST /api/v2/customers-hub/assignment/auto-assign
     * 
     * Auto-assign customers based on employee rules.
     */
    public function autoAssign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employeeRules' => 'required|array|min:1',
            'employeeRules.*.employeeId' => 'required|string',
            'employeeRules.*.isActive' => 'required|boolean',
            'employeeRules.*.rules' => 'required|array',
            'employeeRules.*.rules.*.id' => 'required|string',
            'employeeRules.*.rules.*.field' => 'required|in:budgetMin,budgetMax,propertyType,city,source',
            'employeeRules.*.rules.*.operator' => 'required|in:equals,greaterThan,lessThan,contains',
            'employeeRules.*.rules.*.value' => 'required|string',
        ]);

        $userId = $this->getTenantUserId($request);

        try {
            $result = $this->assignmentService->autoAssign($userId, $validated['employeeRules']);

            // Invalidate caches
            Cache::forget("ch:assignment:employees:{$userId}");

            return $this->successWithSpec($result, 'Customers assigned successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorWithSpec($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Auto-assign error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return $this->errorWithSpec('Internal server error', 500);
        }
    }

    /**
     * POST /api/v2/customers-hub/assignment/assign
     *
     * Manually assign property requests (leads) to an employee. Accepts requestIds or customerIds (backward compat).
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'requestIds' => 'nullable|array|min:1',
            'requestIds.*' => 'integer',
            'customerIds' => 'nullable|array|min:1',
            'customerIds.*' => 'string',
            'employeeId' => 'required|string|exists:users,id',
        ]);

        $ids = !empty($validated['requestIds'])
            ? array_map('intval', $validated['requestIds'])
            : array_map('intval', $validated['customerIds'] ?? []);
        if (empty($ids)) {
            return $this->errorWithSpec('At least one of requestIds or customerIds is required', 422);
        }

        $userId = $this->getTenantUserId($request);

        try {
            $result = $this->assignmentService->manualAssign(
                $userId,
                $ids,
                $validated['employeeId']
            );

            // Invalidate caches
            Cache::forget("ch:assignment:employees:{$userId}");

            return $this->successWithSpec($result, 'Requests assigned successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->errorWithSpec($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('Manual assign error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return $this->errorWithSpec('Internal server error', 500);
        }
    }

    /**
     * POST /api/v2/customers-hub/assignment/rules
     * 
     * Save assignment rules for employees.
     */
    public function saveRules(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employeeRules' => 'required|array|min:1',
            'employeeRules.*.employeeId' => 'required|string',
            'employeeRules.*.isActive' => 'required|boolean',
            'employeeRules.*.rules' => 'required|array',
            'employeeRules.*.rules.*.id' => 'nullable|string',
            'employeeRules.*.rules.*.field' => 'required|in:budgetMin,budgetMax,propertyType,city,source',
            'employeeRules.*.rules.*.operator' => 'required|in:equals,greaterThan,lessThan,contains',
            'employeeRules.*.rules.*.value' => 'required|string',
        ]);

        $userId = $this->getTenantUserId($request);

        try {
            $result = $this->assignmentService->saveRules($userId, $validated['employeeRules']);

            return $this->successWithSpec($result, 'Assignment rules saved successfully');
        } catch (\Exception $e) {
            Log::error('Save rules error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return $this->errorWithSpec('Internal server error', 500);
        }
    }

    /**
     * GET /api/v2/customers-hub/assignment/rules
     * 
     * Get assignment rules for all employees.
     */
    public function getRules(Request $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        
        try {
            $rules = $this->assignmentService->getRules($userId);

            return $this->successWithSpec(
                ['rules' => $rules],
                'Assignment rules retrieved successfully'
            );
        } catch (\Exception $e) {
            Log::error('Get rules error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId
            ]);
            return $this->errorWithSpec('Internal server error', 500);
        }
    }

    /**
     * Success response with API specification format.
     *
     * @param array $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function successWithSpec(array $data, string $message, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * Error response with API specification format.
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return JsonResponse
     */
    protected function errorWithSpec(string $message, int $code = 422, array $errors = []): JsonResponse
    {
        $response = [
            'status' => 'error',
            'code' => $code,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Get the tenant user ID from request.
     *
     * @param Request $request
     * @return int
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
