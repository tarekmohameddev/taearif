<?php

namespace App\Observers;

use App\Models\Membership;
use App\Support\CacheInvalidationHelper;
use Illuminate\Support\Facades\Cache;

class MembershipObserver
{
    /**
     * Handle the Membership "created" event.
     * Clear cache when a new membership is created.
     */
    public function created(Membership $membership): void
    {
        $this->clearMembershipCache($membership->user_id);
    }

    /**
     * Handle the Membership "updated" event.
     * Clear cache when membership is updated (status, expire_date, etc. may have changed).
     */
    public function updated(Membership $membership): void
    {
        $this->clearMembershipCache($membership->user_id);
        
        // Also clear cache for original user_id if it changed
        if ($membership->isDirty('user_id') && $membership->getOriginal('user_id')) {
            $this->clearMembershipCache($membership->getOriginal('user_id'));
        }
    }

    /**
     * Handle the Membership "deleted" event.
     * Clear cache when a membership is deleted.
     */
    public function deleted(Membership $membership): void
    {
        $this->clearMembershipCache($membership->user_id);
    }

    /**
     * Clear the active membership cache for a specific user.
     * Also clears related caches that depend on membership data.
     *
     * @param int $userId
     * @return void
     */
    private function clearMembershipCache(int $userId): void
    {
        // Primary membership cache (standardized colon format)
        Cache::forget("active_membership:{$userId}");
        
        // Membership package cache used in side menus
        Cache::forget("membership_package:{$userId}");
        
        // Membership affects tenant profile AND employee profiles (they reflect tenant membership/quota).
        // Clear profile-related caches for tenant + all employees.
        CacheInvalidationHelper::clearTenantProfileCachesAuto($userId);
    }
}
