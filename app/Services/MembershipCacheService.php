<?php

namespace App\Services;

use App\Models\Membership;
use Illuminate\Support\Facades\Cache;

class MembershipCacheService
{
    /**
     * Get active membership for a user with caching
     * 
     * @param int $userId
     * @return \App\Models\Membership|null
     */
    public static function getActiveMembership($userId)
    {
        $cacheKey = "active_membership:{$userId}";

        // Defensive: older code paths may have cached a boolean/array/etc.
        // We only allow a Membership model (or null) under this key.
        $cached = Cache::get($cacheKey);
        if (!is_null($cached) && !($cached instanceof Membership)) {
            Cache::forget($cacheKey);
            \Log::warning('Invalid active_membership cache value purged', [
                'user_id' => $userId,
                'cache_key' => $cacheKey,
                'cached_type' => gettype($cached),
            ]);
        }

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            return Membership::where('user_id', $userId)
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->with('package')
                ->first();
        });
    }

    /**
     * Clear membership cache for a user
     * 
     * @param int $userId
     * @return void
     */
    public static function clearCache($userId)
    {
        Cache::forget("active_membership:{$userId}");
    }

    /**
     * Clear membership cache for all users (use with caution)
     * 
     * WARNING: This method previously used Cache::flush() which clears ALL cache
     * for ALL tenants - a nuclear option that should be avoided.
     * 
     * With file cache driver, we cannot do wildcard deletion.
     * For Redis, consider using cache tags instead.
     * 
     * @deprecated This method is dangerous. Use clearCache($userId) for specific users instead.
     * @return void
     */
    public static function clearAllCache()
    {
        // DO NOT use Cache::flush() - it clears ALL cache for ALL tenants!
        // This includes property caches, customer caches, dashboard caches, etc.
        //
        // With file cache driver, there's no safe way to clear all membership caches
        // without affecting other caches. Options:
        // 1. Clear specific user caches via clearCache($userId)
        // 2. Wait for TTL expiration (5 minutes)
        // 3. Migrate to Redis and use cache tags
        //
        // Keeping this method empty to prevent accidental cache flush.
        // If you need to clear all caches, use artisan cache:clear via CLI.
        \Log::warning('MembershipCacheService::clearAllCache() called but disabled to prevent Cache::flush()');
    }
}

