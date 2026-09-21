<?php

namespace App\Services\TenantBrand;

use Illuminate\Support\Facades\DB;

final class TenantBrandRefreshScheduler
{
    /** @param array<string, object|null> $overrides */
    public function refreshAfterCommit(int $tenantId, array $overrides = []): void
    {
        DB::afterCommit(function () use ($tenantId, $overrides) {
            app(TenantBrandRefreshService::class)->refreshSafely($tenantId, $overrides);
        });
    }

    /** @param list<string> $identifiers */
    public function invalidateAfterCommit(int $tenantId, array $identifiers = []): void
    {
        DB::afterCommit(function () use ($tenantId, $identifiers) {
            app(TenantBrandRefreshService::class)->invalidateSafely($tenantId, $identifiers);
        });
    }
}
