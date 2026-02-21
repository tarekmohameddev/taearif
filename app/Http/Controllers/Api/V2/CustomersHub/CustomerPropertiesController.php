<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\CustomerAssignedPropertyService;
use App\Http\Requests\Api\V2\CustomersHub\AddPropertyRequest;

/**
 * CustomerPropertiesController
 *
 * Assign and list properties (listings) for a customer via api_customer_assigned_property pivot.
 *
 * Routes:
 * - POST   /api/v2/customers-hub/customers/{customerId}/properties
 * - GET    /api/v2/customers-hub/customers/{customerId}/properties
 * - DELETE /api/v2/customers-hub/customers/{customerId}/properties/{propertyId}
 */
class CustomerPropertiesController extends ApiController
{
    public function __construct(
        private CustomerAssignedPropertyService $assignedPropertyService
    ) {
    }

    /**
     * POST /api/v2/customers-hub/customers/{customerId}/properties
     * Assign one property (listing) to the customer.
     */
    public function addProperty(AddPropertyRequest $request, int $customerId): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $propertyId = (int) $validated['propertyId'];

        $result = $this->assignedPropertyService->attach($userId, $customerId, $propertyId);

        if ($result === false) {
            $customerExists = DB::table('api_customers')
                ->where('id', $customerId)
                ->where('user_id', $userId)
                ->exists();
            if (!$customerExists) {
                return $this->error('Customer not found.', 404);
            }
            $propertyExists = DB::table('user_properties')
                ->where('id', $propertyId)
                ->where('user_id', $userId)
                ->exists();
            if (!$propertyExists) {
                return $this->error('Property not found or does not belong to your account.', 404);
            }
            return $this->error('This property is already assigned to this customer.', 409);
        }

        return $this->success([
            'customerId' => $result['customerId'],
            'propertyId' => $result['propertyId'],
            'attachedAt' => $result['attachedAt'],
            'message' => 'Property assigned to customer successfully',
        ], 201);
    }

    /**
     * GET /api/v2/customers-hub/customers/{customerId}/properties
     * List all properties assigned to the customer.
     */
    public function listProperties(Request $request, int $customerId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $limit = (int) $request->input('limit', 100);
        $limit = $limit <= 0 ? 100 : min($limit, 500);
        $offset = max((int) $request->input('offset', 0), 0);

        $result = $this->assignedPropertyService->listForCustomer($userId, $customerId, $limit, $offset);

        if ($result['total'] === 0 && empty($result['properties'])) {
            $customerExists = DB::table('api_customers')
                ->where('id', $customerId)
                ->where('user_id', $userId)
                ->exists();
            if (!$customerExists) {
                return $this->error('Customer not found.', 404);
            }
        }

        return $this->success($result);
    }

    /**
     * DELETE /api/v2/customers-hub/customers/{customerId}/properties/{propertyId}
     * Remove one property assignment from the customer.
     */
    public function removeProperty(Request $request, int $customerId, int $propertyId): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $deleted = $this->assignedPropertyService->detach($userId, $customerId, $propertyId);

        if (!$deleted) {
            $customerExists = DB::table('api_customers')
                ->where('id', $customerId)
                ->where('user_id', $userId)
                ->exists();
            if (!$customerExists) {
                return $this->error('Customer not found.', 404);
            }
            $propertyExists = DB::table('user_properties')
                ->where('id', $propertyId)
                ->where('user_id', $userId)
                ->exists();
            if (!$propertyExists) {
                return $this->error('Property not found or does not belong to your account.', 404);
            }
            return $this->error('Assignment not found or already removed.', 404);
        }

        return $this->success([
            'message' => 'Property unassigned from customer successfully.',
            'customerId' => $customerId,
            'propertyId' => $propertyId,
        ]);
    }

    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
