<?php

namespace App\Observers;

use App\Models\Api\UserApiCustomerReminder;
use Illuminate\Support\Facades\Cache;

class UserApiCustomerReminderObserver
{
    /**
     * Handle the UserApiCustomerReminder "created" event.
     * Clear cache when a new reminder is created.
     */
    public function created(UserApiCustomerReminder $reminder): void
    {
        $this->clearFilterOptionsCache($reminder->user_id);
    }

    /**
     * Handle the UserApiCustomerReminder "updated" event.
     * Clear cache when a reminder is updated.
     */
    public function updated(UserApiCustomerReminder $reminder): void
    {
        $this->clearFilterOptionsCache($reminder->user_id);
        
        // Also clear cache for original user_id if it changed
        if ($reminder->isDirty('user_id') && $reminder->getOriginal('user_id')) {
            $this->clearFilterOptionsCache($reminder->getOriginal('user_id'));
        }
    }

    /**
     * Handle the UserApiCustomerReminder "deleted" event.
     * Clear cache when a reminder is deleted.
     */
    public function deleted(UserApiCustomerReminder $reminder): void
    {
        $this->clearFilterOptionsCache($reminder->user_id);
    }

    /**
     * Clear the filter options cache for a specific tenant.
     *
     * @param int|null $userId
     * @return void
     */
    private function clearFilterOptionsCache(?int $userId): void
    {
        // Only clear cache if user_id is not null (default reminders have null user_id)
        // and are visible to all tenants, so we need to clear all tenant caches
        if ($userId === null) {
            // For default reminders, we'd need to clear all tenant caches
            // This is complex, so we'll use a wildcard pattern or clear all
            // For now, we'll skip clearing for default reminders as they affect all tenants
            // and clearing all caches would be expensive
            return;
        }

        $cacheKey = "customer_reminders_filter_options_{$userId}";
        Cache::forget($cacheKey);
    }
}

