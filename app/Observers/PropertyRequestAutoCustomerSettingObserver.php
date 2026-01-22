<?php

namespace App\Observers;

use App\Models\Api\PropertyRequestAutoCustomerSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for PropertyRequestAutoCustomerSetting model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * Clears property request auto customer settings and customer defaults caches
 * when settings are created, updated, or deleted.
 */
class PropertyRequestAutoCustomerSettingObserver
{
    /**
     * Handle the PropertyRequestAutoCustomerSetting "created" event.
     * Clear settings cache when new settings are created.
     */
    public function created(PropertyRequestAutoCustomerSetting $setting): void
    {
        $this->clearSettingsCaches($setting->user_id);
    }

    /**
     * Handle the PropertyRequestAutoCustomerSetting "updated" event.
     * Clear both settings and defaults caches when settings are updated.
     */
    public function updated(PropertyRequestAutoCustomerSetting $setting): void
    {
        $this->clearSettingsCaches($setting->user_id);
        
        // Also clear cache for original user_id if it changed
        if ($setting->isDirty('user_id') && $setting->getOriginal('user_id')) {
            $this->clearSettingsCaches($setting->getOriginal('user_id'));
        }
    }

    /**
     * Handle the PropertyRequestAutoCustomerSetting "deleted" event.
     * Clear both caches when settings are deleted.
     */
    public function deleted(PropertyRequestAutoCustomerSetting $setting): void
    {
        $this->clearSettingsCaches($setting->user_id);
    }

    /**
     * Clear caches related to property request auto customer settings.
     *
     * @param int $userId
     * @return void
     */
    private function clearSettingsCaches(int $userId): void
    {
        // Clear auto customer settings cache
        Cache::forget("property_request_auto_customer_settings:{$userId}");
        
        // Clear customer defaults cache (depends on settings)
        Cache::forget("customer_defaults:{$userId}");
    }
}
