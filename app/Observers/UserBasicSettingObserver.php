<?php

namespace App\Observers;

use App\Models\User\BasicSetting;
use App\Support\CacheInvalidationHelper;

class UserBasicSettingObserver
{
    public function created(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
    }

    public function updated(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
    }

    public function deleted(BasicSetting $basicSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $basicSetting->user_id);
    }
}

