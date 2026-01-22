<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\UserFacade;
use App\Support\CacheInvalidationHelper;

/**
 * Observer for UserFacade model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * Clears property facades cache when facades are created/updated/deleted.
 */
class UserFacadeObserver
{
    /**
     * Handle the UserFacade "created" event.
     */
    public function created(UserFacade $facade): void
    {
        CacheInvalidationHelper::clearPropertyFacadesCache();
    }

    /**
     * Handle the UserFacade "updated" event.
     */
    public function updated(UserFacade $facade): void
    {
        CacheInvalidationHelper::clearPropertyFacadesCache();
    }

    /**
     * Handle the UserFacade "deleted" event.
     */
    public function deleted(UserFacade $facade): void
    {
        CacheInvalidationHelper::clearPropertyFacadesCache();
    }
}
