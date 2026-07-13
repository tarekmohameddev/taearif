<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantWebsiteSavePagesLog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SavePagesActivityLogQueryService
{
    public function paginateForTenant(int $tenantId, Request $request): LengthAwarePaginator
    {
        $query = TenantWebsiteSavePagesLog::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        return $query->paginate($perPage);
    }
}
