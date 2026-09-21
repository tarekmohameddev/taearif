<?php

namespace App\Observers;

use App\Models\Api\ApiDomainSetting;
use App\Support\CacheInvalidationHelper;
use App\Services\TenantBrand\TenantBrandRefreshScheduler;
use App\Services\TenantWebsite\TenantIdentifierNormalizer;

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
        $this->invalidateBrand($domainSetting, [$domainSetting->custom_name]);
    }

    public function updated(ApiDomainSetting $domainSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $domainSetting->user_id);
        $this->invalidateBrand($domainSetting, [
            $domainSetting->getOriginal('custom_name'),
            $domainSetting->custom_name,
        ]);
    }

    public function deleted(ApiDomainSetting $domainSetting): void
    {
        CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $domainSetting->user_id);
        $this->invalidateBrand($domainSetting, [$domainSetting->custom_name]);
    }

    private function invalidateBrand(ApiDomainSetting $domainSetting, array $identifiers): void
    {
        $normalizer = app(TenantIdentifierNormalizer::class);
        $normalized = [];
        foreach ($identifiers as $identifier) {
            if (is_string($identifier) && trim($identifier) !== '') {
                $normalized[] = $normalizer->normalize($identifier);
            }
        }

        app(TenantBrandRefreshScheduler::class)->invalidateAfterCommit(
            (int) $domainSetting->user_id,
            array_values(array_unique($normalized))
        );
    }
}
