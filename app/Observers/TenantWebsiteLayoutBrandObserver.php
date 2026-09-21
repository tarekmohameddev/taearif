<?php

namespace App\Observers;

use App\Models\TenantWebsiteLayout;
use App\Services\TenantBrand\TenantBrandRefreshScheduler;

final class TenantWebsiteLayoutBrandObserver
{
    public function saved(TenantWebsiteLayout $model): void
    {
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $model->user_id,
            ['layout' => $model]
        );
    }

    public function deleted(TenantWebsiteLayout $model): void
    {
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $model->user_id,
            ['layout' => null]
        );
    }
}
