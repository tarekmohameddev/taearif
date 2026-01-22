<?php

namespace App\Observers;

use App\Models\Membership;
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
        
        // User profile cache (membership affects profile data)
        // Note: We clear all variants since we don't know the ownerId context here
        // The user profile cache key is user:profile:{userId}:{ownerId}
        // For tenant users, userId == ownerId; for employees, we'd need tenant_id
        // Clearing by userId covers the tenant case; employee cases expire by TTL
        Cache::forget("user:profile:{$userId}:{$userId}");
    }
}
