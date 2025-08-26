<?php

namespace App\Http\Controllers\Api\V1\Logs;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Logs\CardLog;

class CardLogController extends Controller
{
    public function index(Request $request, int $id)
    {
        $u = $request->user();
        $tenantId = method_exists($u, 'tenantOwnerId')
            ? $u->tenantOwnerId()
            : ($u->tenant_id ?: $u->id);

        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        $q = CardLog::query()
            ->where('tenant_id', $tenantId)
            ->where('card_id', $id)
            ->when($request->filled('action'), fn($qb) => $qb->where('action', $request->action))
            ->when($request->filled('actor_type'), fn($qb) => $qb->where('actor_type', $request->actor_type))
            ->when($request->filled('actor_id'), fn($qb) => $qb->where('actor_id', (int) $request->actor_id))
            ->orderByDesc('id');

        $paginator = $q->paginate($perPage);

        $rows = $paginator->getCollection()->map(function ($log) {
            return [
                'id'         => $log->id,
                'action'     => $log->action,
                'actor'      => ['id' => $log->actor_id, 'type' => $log->actor_type],
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'note'       => $log->note,
                'changes'    => $log->changes,
                'created_at' => $log->created_at?->toIsoString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'logs' => $rows,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
            ],
        ]);
    }
}
