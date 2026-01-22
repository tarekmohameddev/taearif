<?php

namespace App\Observers;

use App\Models\Api\ApiSidebarItem;
use App\Support\CacheInvalidationHelper;

/**
 * Observer for ApiSidebarItem model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * Note: Side menu caches are per-user with pattern:
 * side_menus:v1:{userId}:{ownerId}:{isOwner}:{isAffiliateApproved}
 * 
 * With file cache driver, we cannot do wildcard deletion.
 * These caches have a 5-minute TTL, so changes will be visible within 5 minutes.
 * 
 * This observer is registered for documentation purposes and future Redis migration.
 */
class ApiSidebarItemObserver
{
    /**
     * Handle the ApiSidebarItem "created" event.
     */
    public function created(ApiSidebarItem $item): void
    {
        // File cache doesn't support wildcard deletion
        // User-specific side_menus caches will expire by TTL (5 min)
        CacheInvalidationHelper::clearAllSidebarCaches();
    }

    /**
     * Handle the ApiSidebarItem "updated" event.
     */
    public function updated(ApiSidebarItem $item): void
    {
        CacheInvalidationHelper::clearAllSidebarCaches();
    }

    /**
     * Handle the ApiSidebarItem "deleted" event.
     */
    public function deleted(ApiSidebarItem $item): void
    {
        CacheInvalidationHelper::clearAllSidebarCaches();
    }
}
