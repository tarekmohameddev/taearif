<?php

namespace App\Observers;

use App\Models\User\BasicSetting;
use App\Support\CacheInvalidationHelper;
use App\Services\TenantBrand\TenantBrandRefreshScheduler;

class UserBasicSettingObserver
{
    public function created(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
        $this->refreshBrand($basicSetting);
    }

    public function updated(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
        $this->refreshBrand($basicSetting);
    }

    public function deleted(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $basicSetting->user_id,
            ['basic' => null]
        );
    }

    private function refreshBrand(BasicSetting $basicSetting): void
    {
        app(TenantBrandRefreshScheduler::class)->refreshAfterCommit(
            (int) $basicSetting->user_id,
            ['basic' => $basicSetting]
        );
    }
}

