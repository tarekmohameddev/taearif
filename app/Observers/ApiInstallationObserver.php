<?php

namespace App\Observers;

use App\Models\Api\ApiInstallation;
use App\Support\CacheInvalidationHelper;

/**
 * Observer for ApiInstallation model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * Clears installed_apps and side_menus caches when apps are installed/uninstalled.
 */
class ApiInstallationObserver
{
    /**
     * Handle the ApiInstallation "created" event.
     * New app installation - clear user's app and menu caches.
     */
    public function created(ApiInstallation $installation): void
    {
        $this->clearInstallationCaches($installation);
    }

    /**
     * Handle the ApiInstallation "updated" event.
     * Status changed (installed/uninstalled/trialing) - clear caches.
     */
    public function updated(ApiInstallation $installation): void
    {
        $this->clearInstallationCaches($installation);
        
        // If user_id changed, clear old user's caches too
        if ($installation->isDirty('user_id') && $installation->getOriginal('user_id')) {
            $oldUserId = $installation->getOriginal('user_id');
            CacheInvalidationHelper::clearInstalledAppsCache($oldUserId);
            CacheInvalidationHelper::clearSideMenusCache($oldUserId);
        }
    }

    /**
     * Handle the ApiInstallation "deleted" event.
     * App uninstalled - clear user's caches.
     */
    public function deleted(ApiInstallation $installation): void
    {
        $this->clearInstallationCaches($installation);
    }

    /**
     * Clear caches related to app installation.
     */
    private function clearInstallationCaches(ApiInstallation $installation): void
    {
        $userId = $installation->user_id;
        
        if ($userId) {
            // Clear installed apps cache
            CacheInvalidationHelper::clearInstalledAppsCache($userId);
            
            // Clear side menus cache (installed apps appear in sidebar)
            CacheInvalidationHelper::clearSideMenusCache($userId);
        }
    }
}
