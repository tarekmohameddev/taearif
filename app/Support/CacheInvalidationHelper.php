<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Centralized cache invalidation helper.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * 
 * This helper provides targeted cache clearing methods to avoid
 * using Cache::flush() which clears ALL cache for ALL tenants.
 */
class CacheInvalidationHelper
{
    /**
     * Clear user profile cache for a specific user.
     * Called when user data, membership, or settings change.
     *
     * @param int $userId
     * @param int|null $ownerId If null, assumes userId is the owner (tenant)
     */
    public static function clearUserProfileCache(int $userId, ?int $ownerId = null): void
    {
        $ownerId = $ownerId ?? $userId;
        Cache::forget("user:profile:{$userId}:{$ownerId}");
    }

    /**
     * Clear cached permissions payload for a specific user.
     * Note: This is separate from Spatie's own permission cache.
     *
     * @param int $userId
     * @param int|null $ownerId If null, assumes userId is the owner (tenant)
     */
    public static function clearUserPermissionsCache(int $userId, ?int $ownerId = null): void
    {
        $ownerId = $ownerId ?? $userId;
        Cache::forget("user:permissions:{$userId}:{$ownerId}");
    }

    /**
     * Clear side menus cache for a specific user.
     * Called when permissions, sidebar items, or apps change.
     *
     * Note: Side menu cache key includes multiple factors:
     * side_menus:v1:{userId}:{ownerId}:{isOwner}:{isAffiliateApproved}
     * 
     * Since we can't know all variants, this clears the known patterns.
     * Consider using shorter TTLs or a cache key registry for full coverage.
     *
     * @param int $userId
     * @param int|null $ownerId
     */
    public static function clearSideMenusCache(int $userId, ?int $ownerId = null): void
    {
        $ownerId = $ownerId ?? $userId;
        
        // Clear all possible variants (owner/not owner, affiliate/not affiliate)
        foreach (['1', '0'] as $isOwner) {
            foreach (['1', '0'] as $isAffiliate) {
                Cache::forget("side_menus:v1:{$userId}:{$ownerId}:{$isOwner}:{$isAffiliate}");
            }
        }
    }

    /**
     * Clear installed apps cache for a specific user.
     *
     * @param int $userId
     */
    public static function clearInstalledAppsCache(int $userId): void
    {
        Cache::forget("installed_apps:{$userId}");
    }

    /**
     * Clear membership-related caches for a user.
     *
     * @param int $userId
     */
    public static function clearMembershipCaches(int $userId): void
    {
        Cache::forget("active_membership:{$userId}");
        Cache::forget("membership_package:{$userId}");
    }

    /**
     * Clear dashboard summary caches for a user.
     *
     * @param int $userId
     * @param string|null $tenantId The tenant username/ID for analytics
     */
    public static function clearDashboardCaches(int $userId, ?string $tenantId = null): void
    {
        Cache::forget("summary:db:{$userId}");
        
        if ($tenantId) {
            Cache::forget("dashboard:summary:{$tenantId}:{$userId}");
        }
    }

    /**
     * Clear tenant user lookup cache.
     *
     * @param int $tenantId
     */
    public static function clearTenantUserCache(int $tenantId): void
    {
        Cache::forget("tenant_user_{$tenantId}");
    }

    /**
     * Clear property categories cache (global).
     */
    public static function clearPropertyCategoriesCache(): void
    {
        Cache::forget('api_property_categories_list');
    }

    /**
     * Clear property facades cache (global).
     */
    public static function clearPropertyFacadesCache(): void
    {
        Cache::forget('api_property_facades_list');
    }

    /**
     * Clear all sidebar-related caches when admin changes sidebar items.
     * This is more targeted than Cache::flush().
     *
     * Note: With file cache driver, we cannot do wildcard deletion.
     * This clears the global sidebar items; user-specific caches
     * will expire by TTL (5 minutes).
     */
    public static function clearAllSidebarCaches(): void
    {
        // Global sidebar items are not user-specific, but the menus are
        // Since we can't enumerate all user cache keys with file driver,
        // we rely on short TTL (5 min) for user-specific side_menus caches
        // 
        // What we CAN do is ensure any cached sidebar item queries are cleared
        // Most sidebar item queries are not cached separately
    }

    /**
     * Clear all app-related caches when admin changes marketplace apps.
     * More targeted than Cache::flush().
     */
    public static function clearAllAppCaches(): void
    {
        // Similar limitation as sidebar - user-specific installed_apps
        // caches expire by TTL (5 min)
    }

    /**
     * Clear caches for a tenant and all their employees.
     * Useful when tenant-wide settings change.
     *
     * @param int $tenantId
     * @param array $employeeIds
     */
    public static function clearTenantUserCaches(int $tenantId, array $employeeIds = []): void
    {
        // Clear tenant's own caches
        self::clearUserProfileCache($tenantId);
        self::clearUserPermissionsCache($tenantId);
        self::clearSideMenusCache($tenantId);
        self::clearInstalledAppsCache($tenantId);
        self::clearMembershipCaches($tenantId);
        
        // Clear employee caches (they reference tenant's data)
        foreach ($employeeIds as $employeeId) {
            self::clearUserProfileCache($employeeId, $tenantId);
            self::clearUserPermissionsCache($employeeId, $tenantId);
            self::clearSideMenusCache($employeeId, $tenantId);
            self::clearInstalledAppsCache($employeeId);
        }
    }

    /**
     * Clear profile-related caches for a tenant + all employees.
     * Use this when tenant-wide settings change (domains/basic settings/etc).
     */
    public static function clearTenantProfileCachesAuto(int $tenantId): void
    {
        $employeeIds = User::query()
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->all();

        // Profile payload includes permissions array; clear both.
        self::clearUserProfileCache($tenantId);
        self::clearUserPermissionsCache($tenantId);
        self::clearSideMenusCache($tenantId);

        foreach ($employeeIds as $employeeId) {
            self::clearUserProfileCache((int) $employeeId, $tenantId);
            self::clearUserPermissionsCache((int) $employeeId, $tenantId);
            self::clearSideMenusCache((int) $employeeId, $tenantId);
        }
    }
}
