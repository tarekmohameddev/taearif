<?php

namespace App\Observers;

use App\Models\Api\ApiDomainSetting;
use App\Support\CacheInvalidationHelper;

/**
 * Tenant/profile cache invalidation for domain setting changes.
 *
 * Vercel inventory and admin health counters are invalidated explicitly from
 * mutation paths via {@see \App\Services\Vercel\VercelDomainCache::invalidateAdminCaches()}
 * to avoid storms during chunked hourly sync.
 */
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
