<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V2\CustomersHub\IgnoredCustomersIndexRequest;
use App\Http\Requests\Api\V2\CustomersHub\IgnoredCustomersStoreRequest;
use App\Domain\CustomersHub\Services\IgnoredCustomersService;

/**
 * IgnoredCustomersController
 *
 * API endpoints to manage the tenant-level ignore list.
 * When a customer/phone is on the ignore list, new property requests are blocked.
 *
 * Routes:
 * - GET    /api/v2/customers-hub/ignored-customers
 * - POST   /api/v2/customers-hub/ignored-customers
 * - DELETE /api/v2/customers-hub/ignored-customers/{id}
 */
class IgnoredCustomersController extends ApiController
{
    public function __construct(private IgnoredCustomersService $service) {}

    /**
     * GET /api/v2/customers-hub/ignored-customers
     *
     * Paginated list of ignored customers for the tenant.
     */
    public function index(IgnoredCustomersIndexRequest $request): JsonResponse
    {
        $userId = $this->getTenantUserId($request);
        $validated = $request->validated();

        $paginator = $this->service->list($userId, $validated);

        return $this->success([
            'data'       => $paginator->items(),
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v2/customers-hub/ignored-customers
     *
     * Add a customer/phone to the ignore list.
     * Body: { phone?: string, customer_id?: int, reason?: string }
     * At least one of phone or customer_id is required.
     */
    public function store(IgnoredCustomersStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $createdBy = $request->user()->id;

        try {
            $entry = $this->service->add(
                tenantUserId: $userId,
                phone: $validated['phone'] ?? null,
                customerId: isset($validated['customer_id']) ? (int) $validated['customer_id'] : null,
                reason: $validated['reason'] ?? null,
                createdBy: $createdBy
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(['entry' => $entry], 201);
    }

    /**
     * DELETE /api/v2/customers-hub/ignored-customers/{id}
     *
     * Remove an entry from the ignore list.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $this->getTenantUserId($request);

        $deleted = $this->service->remove($userId, $id);

        if (!$deleted) {
            return $this->error('Entry not found.', 404);
        }

        return $this->success(['message' => 'Entry removed from ignore list.']);
    }

    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
