<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Domain\CustomersHub\Services\CustomerDetailService;
use App\Domain\CustomersHub\Services\CustomersListService;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends ApiController
{
    public function __construct(
        private CustomersListService $customersListService,
        private CustomerDetailService $customerDetailService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        $filters = [];
        if (($search = $request->query('search')) !== null && $search !== '') {
            $filters['search'] = (string) $search;
        }
        if (($stageId = $request->query('stage_id')) !== null && $stageId !== '') {
            $filters['stage'] = [(string) $stageId];
        }
        if (($priorityId = $request->query('priority_id')) !== null && $priorityId !== '') {
            $filters['priority'] = [(int) $priorityId];
        }

        $result = $this->customersListService->getList($userId, $filters, $page, $limit);

        $items = collect($result['customers'] ?? [])
            ->map(function (array $c) {
                return [
                    'id' => (int) ($c['id'] ?? 0),
                    'name' => $c['name'] ?? null,
                    'phone' => $c['phone'] ?? null,
                    'stage' => [
                        'id' => $c['stage']['id'] ?? null,
                        'name' => $c['stage']['name'] ?? null,
                    ],
                    'priority' => [
                        'id' => $c['priority']['id'] ?? null,
                        'name' => $c['priority']['name'] ?? null,
                    ],
                    'created_at' => $c['createdAt'] ?? null,
                ];
            })
            ->values()
            ->all();

        $pagination = $result['pagination'] ?? [];

        return $this->success([
            'items' => $items,
            'pagination' => [
                'total' => (int) ($pagination['total'] ?? 0),
                'page' => (int) ($pagination['page'] ?? $page),
                'limit' => (int) ($pagination['limit'] ?? $limit),
                'last_page' => (int) ($pagination['totalPages'] ?? 1),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $data = $this->customerDetailService->getCustomerDetails($userId, $id);
        if (! $data) {
            return $this->error('Not found', 404);
        }

        return $this->success($data);
    }

    public function updateStage(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stage_id' => 'required|string|exists:customers_hub_stages,stage_id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $userId = $this->tenantUserId($request);

        $ok = $this->customerDetailService->updateCustomer($userId, $id, [
            'customers_hub_stage_id' => $validator->validated()['stage_id'],
        ]);

        if (! $ok) {
            return $this->error('Not found', 404);
        }

        return $this->success([
            'message' => 'Stage updated successfully',
        ]);
    }

    public function updatePriority(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'priority_id' => 'required|integer|exists:users_api_customers_priorities,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $userId = $this->tenantUserId($request);

        $ok = $this->customerDetailService->updateCustomer($userId, $id, [
            'priority_id' => (int) $validator->validated()['priority_id'],
        ]);

        if (! $ok) {
            return $this->error('Not found', 404);
        }

        return $this->success([
            'message' => 'Priority updated successfully',
        ]);
    }

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
