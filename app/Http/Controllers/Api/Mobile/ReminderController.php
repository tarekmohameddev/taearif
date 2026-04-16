<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $filter = (string) ($request->query('filter', 'today') ?? 'today');
        $page = max(1, (int) $request->query('page', 1));
        $limit = max(1, (int) $request->query('limit', 20));

        $query = Reminder::query()
            ->where('user_id', $userId)
            ->with(['customer:id,name'])
            ->orderBy('datetime', 'asc');

        if ($filter === 'today') {
            $query->whereDate('datetime', today())
                ->where('status', 'pending');
        } elseif ($filter === 'upcoming') {
            $query->where('datetime', '>', now())
                ->where('status', 'pending');
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(function (Reminder $r) {
                return [
                    'id' => (int) $r->id,
                    'title' => $r->title,
                    'notes' => $r->notes ?? $r->description,
                    'customer' => $r->customer ? [
                        'id' => (int) $r->customer->id,
                        'name' => $r->customer->name,
                    ] : null,
                    'due_at' => $r->datetime?->toIso8601String(),
                    'is_done' => ($r->status ?? null) === 'completed',
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

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
