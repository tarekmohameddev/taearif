<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\Api\Rms\RmRental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        $query = RmRental::query()
            ->where('user_id', $userId)
            ->with([
                'property.contents:id,property_id,title,language_id',
            ])
            ->orderByDesc('created_at');

        if (($status = $request->query('status')) !== null && $status !== '') {
            $query->where('status', (string) $status);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(function (RmRental $r) {
                $propertyName = $r->property
                    ? (optional($r->property->contents->first())->title ?? ('Unit #' . $r->unit_id))
                    : null;

                return [
                    'id' => (int) $r->id,
                    'property_name' => $propertyName,
                    'tenant_name' => $r->tenant_full_name,
                    'status' => $r->status,
                    'monthly_amount' => $r->base_rent_amount,
                    'next_payment_date' => $r->next_payment_due_date ? (string) $r->next_payment_due_date : null,
                    'contract_end_date' => $r->end_date ? (string) $r->end_date : null,
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

        $row = RmRental::where('user_id', $userId)
            ->with(['property', 'contracts', 'installments', 'payments'])
            ->find($id);

        if (! $row) {
            return $this->error('Not found', 404);
        }

        return $this->success($row->toArray());
    }

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
