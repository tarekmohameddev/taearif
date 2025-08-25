<?php

namespace App\Http\Controllers\Api\V1\Logs\Concerns;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

trait BuildsLogResponses
{
    protected function resolveTenantId(Request $request): int
    {
        $u = $request->user();
        // rule:
        // tenant owner => tenant_id = null AND account_type = 'tenant'
        // employee     => tenant_id = number AND account_type = 'employee'
        if (is_null($u->tenant_id) && $u->account_type === 'tenant') {
            return (int) $u->id;
        }
        return (int) $u->tenant_id; // employee’s owner id
    }

    protected function mapLogRow($log): array
    {
        return [
            'id'         => $log->id,
            'action'     => $log->action,
            'actor'      => ['id' => $log->actor_id, 'type' => $log->actor_type],
            'ip'         => $log->ip,
            'user_agent' => $log->user_agent,
            'note'       => $log->note,
            // for separate-table schema use $log->changes
            // for unified-table schema use ['before'=>$log->before,'after'=>$log->after]
            'changes'    => $log->changes ?? ['before' => $log->before ?? null, 'after' => $log->after ?? null],
            'created_at' => optional($log->created_at)->toIsoString(),
        ];
    }

    protected function respondWithLogs(LengthAwarePaginator $paginator)
    {
        $rows = $paginator->getCollection()->map(fn ($log) => $this->mapLogRow($log));

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
