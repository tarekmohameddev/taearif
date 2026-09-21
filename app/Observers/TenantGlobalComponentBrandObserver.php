<?php

namespace App\Observers;

use App\Models\TenantGlobalComponent;
use App\Services\TenantBrand\TenantBrandRefreshScheduler;

final class TenantGlobalComponentBrandObserver
{
    public function saved(TenantGlobalComponent $model): void
    {
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $model->user_id,
            ['globals' => $model]
        );
    }

    public function deleted(TenantGlobalComponent $model): void
    {
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $model->user_id,
            ['globals' => null]
        );
    }
}
