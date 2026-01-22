<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Support\CacheInvalidationHelper;

/**
 * Observer for ApiUserCategory model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * Clears property categories cache when categories are created/updated/deleted.
 */
class ApiUserCategoryObserver
{
    /**
     * Handle the ApiUserCategory "created" event.
     */
    public function created(ApiUserCategory $category): void
    {
        $this->clearCategoryCaches($category);
    }

    /**
     * Handle the ApiUserCategory "updated" event.
     */
    public function updated(ApiUserCategory $category): void
    {
        $this->clearCategoryCaches($category);
    }

    /**
     * Handle the ApiUserCategory "deleted" event.
     */
    public function deleted(ApiUserCategory $category): void
    {
        $this->clearCategoryCaches($category);
    }

    /**
     * Clear caches related to property categories.
     */
    private function clearCategoryCaches(ApiUserCategory $category): void
    {
        // Only clear if this is a property category
        if ($category->type === 'property') {
            CacheInvalidationHelper::clearPropertyCategoriesCache();
        }
    }
}
