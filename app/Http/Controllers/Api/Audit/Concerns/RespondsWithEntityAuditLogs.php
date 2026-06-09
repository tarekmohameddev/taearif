<?php

namespace App\Http\Controllers\Api\Audit\Concerns;

use App\Models\Audit\EntityAuditLog;
use Illuminate\Pagination\LengthAwarePaginator;

trait RespondsWithEntityAuditLogs
{
    protected function mapEntityAuditLogRow(EntityAuditLog $log): array
    {
        return [
            'id' => $log->id,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'action' => $log->action,
            'field_name' => $log->field_name,
            'old_value' => $log->old_value,
            'new_value' => $log->new_value,
            'changed_by' => [
                'id' => $log->changed_by,
                'type' => $log->changed_by_type,
            ],
            'reason' => $log->reason,
            'changed_at' => optional($log->changed_at)->toIso8601String(),
        ];
    }

    protected function respondWithEntityAuditLogs(LengthAwarePaginator $paginator)
    {
        $rows = $paginator->getCollection()->map(fn (EntityAuditLog $log) => $this->mapEntityAuditLogRow($log));

        return response()->json([
            'status' => 'success',
            'data' => [
                'logs' => $rows,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    protected function canViewEntityAuditLog(\App\Models\User $user, string $permission): bool
    {
        return $user->account_type === 'tenant' || $user->can($permission);
    }

    protected function resolveTenantIdForAudit(\App\Models\User $user): int
    {
        if (is_null($user->tenant_id) && $user->account_type === 'tenant') {
            return (int) $user->id;
        }

        return (int) $user->tenant_id;
    }
}
