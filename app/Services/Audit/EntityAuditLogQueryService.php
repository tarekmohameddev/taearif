<?php

namespace App\Services\Audit;

use App\Models\Audit\EntityAuditLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EntityAuditLogQueryService
{
    public function paginateForEntity(
        string $entityType,
        int $entityId,
        int $tenantId,
        Request $request,
    ): LengthAwarePaginator {
        $query = EntityAuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('id');

        if ($request->filled('field_name')) {
            $query->where('field_name', $request->input('field_name'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('from')) {
            $query->where('changed_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('changed_at', '<=', $request->input('to'));
        }

        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        return $query->paginate($perPage);
    }
}
