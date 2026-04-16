<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, (int) $request->query('limit', 20));
        $unreadOnly = filter_var($request->query('unread_only', false), FILTER_VALIDATE_BOOLEAN);

        $query = $request->user()->notifications();
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $paginator = $query->orderByDesc('created_at')->paginate($limit);

        $items = $paginator->getCollection()->map(function ($n) {
            $data = is_array($n->data ?? null) ? $n->data : [];

            return [
                'id' => $n->id,
                'title' => $data['title'] ?? ($data['subject'] ?? null),
                'body' => $data['body'] ?? ($data['message'] ?? null),
                'type' => $n->type,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->toIso8601String(),
            ];
        })->values()->all();

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

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();
        if (! $notification) {
            return $this->error('Not found', 404);
        }

        $notification->markAsRead();

        return $this->success([
            'message' => 'Marked as read',
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success([
            'message' => 'All notifications marked as read',
        ]);
    }
}
