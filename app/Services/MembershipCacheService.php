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
     * @return void
     */
    public static function clearAllCache()
    {
        // Note: This is a simple implementation
        // For production with Redis, consider using cache tags
        Cache::flush();
    }
}

