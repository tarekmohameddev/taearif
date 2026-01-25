<?php

namespace App\Observers;

use App\Models\Api\ApiDomainSetting;
use App\Support\CacheInvalidationHelper;

class ApiDomainSettingObserver
{
    public function created(ApiDomainSetting $domainSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $domainSetting->user_id);
    }

    public function updated(ApiDomainSetting $domainSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $domainSetting->user_id);
    }

    public function deleted(ApiDomainSetting $domainSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $domainSetting->user_id);
    }
}

