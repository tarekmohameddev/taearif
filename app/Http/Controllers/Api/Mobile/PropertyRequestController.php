<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PropertyRequestController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        $query = UserPropertyRequest::query()
            ->where('user_id', $userId)
            ->with([
                'customer:id,name,phone_number',
                'statusOption:id,name_ar,name_en,color',
            ])
            ->orderByDesc('created_at');

        if (($statusId = $request->query('status_id')) !== null && $statusId !== '') {
            $query->where('status_id', (int) $statusId);
        }

        if (($search = $request->query('search')) !== null && $search !== '') {
            $s = '%' + $search + '%';
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', $s)
                    ->orWhere('phone', 'like', $s);
            });
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(function (UserPropertyRequest $r) {
                $statusName = $r->statusOption?->name_en ?? $r->statusOption?->name_ar;

                return [
                    'id' => (int) $r->id,
                    'customer' => [
                        'id' => $r->customer?->id,
                        'name' => $r->customer?->name,
                        'phone' => $r->customer?->phone_number ?? $r->phone,
                    ],
                    'status' => $r->statusOption ? [
                        'id' => (int) $r->statusOption->id,
                        'name' => $statusName,
                        'color' => $r->statusOption->color ?? null,
                    ] : null,
                    'budget_min' => $r->budget_from,
                    'budget_max' => $r->budget_to,
                    'created_at' => $r->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $row = UserPropertyRequest::where('user_id', $userId)
            ->with(['customer', 'statusOption'])
            ->find($id);

        if (! $row) {
            return $this->error('Not found', 404);
        }

        return $this->success($row->toArray());
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|integer|exists:property_request_statuses,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $userId = $this->tenantUserId($request);

        $row = UserPropertyRequest::where('user_id', $userId)->find($id);
        if (! $row) {
            return $this->error('Not found', 404);
        }

        $row->status_id = (int) $validator->validated()['status_id'];
        $row->save();

        return $this->success([
            'message' => 'Status updated successfully',
        ]);
    }

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
