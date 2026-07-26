<?php

namespace App\Observers;

use App\Models\PropertyRequestStatus;
use App\Models\User;
use App\Services\TenantCrmBootstrapService;
use App\Support\CacheInvalidationHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Observer for User model cache invalidation.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 */
class UserObserver
{
    /**
     * Handle the User "updated" event.
     * Clear caches when user profile data changes.
     */
    public function updated(User $user): void
    {
        $this->clearUserCaches($user);
        
        // If this is an employee and tenant_id changed, clear old tenant's caches
        if ($user->isDirty('tenant_id') && $user->getOriginal('tenant_id')) {
            Cache::forget("tenant_employees_{$user->getOriginal('tenant_id')}");
        }
        
        // Clear tenant employees cache if employee is updated
        if ($user->tenant_id) {
            Cache::forget("tenant_employees_{$user->tenant_id}");
        }
        
        // If this is a tenant, clear tenant user lookup cache
        if ($user->account_type === 'tenant') {
            CacheInvalidationHelper::clearTenantUserCache($user->id);
        }
    }

    /**
     * Handle the User "deleted" event.
     * Clear caches when user is deleted.
     */
    public function deleted(User $user): void
    {
        $this->clearUserCaches($user);
        
        // Clear tenant employees cache if employee is deleted
        if ($user->tenant_id) {
            Cache::forget("tenant_employees_{$user->tenant_id}");
        }
        
        // If this is a tenant, clear tenant user lookup cache
        if ($user->account_type === 'tenant') {
            CacheInvalidationHelper::clearTenantUserCache($user->id);
        }
    }

    /**
     * Handle the User "created" event.
     * Clear relevant caches when new user (especially employee) is created.
     */
    public function created(User $user): void
    {
        // Clear tenant employees cache if employee is created
        if ($user->tenant_id) {
            Cache::forget("tenant_employees_{$user->tenant_id}");
        }

        if (($user->account_type ?? null) === 'tenant') {
            PropertyRequestStatus::ensureWorkflowStatusesForTenant((int) $user->id);

            try {
                app(TenantCrmBootstrapService::class)->ensureForTenant((int) $user->id);
            } catch (\Throwable $e) {
                Log::error('Tenant CRM bootstrap failed on user create', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Clear all user-specific caches.
     */
    private function clearUserCaches(User $user): void
    {
        $userId = $user->id;
        $ownerId = $user->tenant_id ?? $userId;
        
        // User profile cache
        CacheInvalidationHelper::clearUserProfileCache($userId, $ownerId);

        // Cached permissions payload (used by /api/user)
        CacheInvalidationHelper::clearUserPermissionsCache($userId, $ownerId);
        
        // Side menus cache (user permissions may have changed)
        CacheInvalidationHelper::clearSideMenusCache($userId, $ownerId);
        
        // Dashboard caches
        CacheInvalidationHelper::clearDashboardCaches($userId, $user->username);

        // If this is a tenant, employees depend on tenant profile fields (membership/quota/domain/company_name/etc)
        if ($user->account_type === 'tenant') {
            CacheInvalidationHelper::clearTenantProfileCachesAuto($userId);
        }
    }
}
