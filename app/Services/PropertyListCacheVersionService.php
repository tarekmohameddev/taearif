<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Property List Cache Versioning Service
 * 
 * Implements cache versioning pattern to enable immediate invalidation
 * of hashed property list cache keys without Redis.
 * 
 * Pattern:
 * - Version key: properties_list_version_{ownerId} (stored with rememberForever)
 * - Cache keys: properties_list_{ownerId}_v{version}_{hash}
 * - Invalidation: Increment version → all old keys become invalid (cache miss)
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 * This service provides logical invalidation via version increment.
 */
class PropertyListCacheVersionService
{
    /**
     * Get current cache version for an owner.
     * Initializes to 1 if not exists.
     * 
     * Uses rememberForever to persist version across cache clears.
     * This ensures version continuity even if cache is flushed.
     * 
     * @param int $ownerId The tenant owner ID
     * @return int Current version number (starts at 1)
     */
    public static function getVersion(int $ownerId): int
    {
        $versionKey = self::getVersionKey($ownerId);
        
        // Use rememberForever to persist version across cache clears
        // If key doesn't exist, initialize to 1
        return (int) Cache::rememberForever($versionKey, function () {
            return 1;
        });
    }

    /**
     * Increment cache version for an owner.
     * Thread-safe using Cache::increment().
     * 
     * This invalidates all property list caches for the owner immediately.
     * All subsequent cache lookups will use the new version, causing cache misses
     * for old keys. Old cache entries expire naturally via TTL (5-10 minutes).
     * 
     * @param int $ownerId The tenant owner ID
     * @return int New version number after increment
     */
    public static function incrementVersion(int $ownerId): int
    {
        $versionKey = self::getVersionKey($ownerId);
        
        // Cache::increment() is atomic and thread-safe
        // If key doesn't exist, it initializes to 0 then increments to 1
        $newVersion = Cache::increment($versionKey);
        
        // If increment returned false/null (shouldn't happen with file driver),
        // fallback to setting version to 2
        if ($newVersion === false || $newVersion === null) {
            Cache::put($versionKey, 2, now()->addYears(10)); // Effectively forever
            return 2;
        }
        
        return (int) $newVersion;
    }

    /**
     * Build versioned cache key for property list.
     * 
     * Format: properties_list_{ownerId}_v{version}_{hash}
     * 
     * @param int $ownerId The tenant owner ID
     * @param string $hash The hash of filters/pagination (from existing logic)
     * @return string Versioned cache key
     */
    public static function buildCacheKey(int $ownerId, string $hash): string
    {
        $version = self::getVersion($ownerId);
        return "properties_list_{$ownerId}_v{$version}_{$hash}";
    }

    /**
     * Get the version key for an owner.
     * 
     * @param int $ownerId The tenant owner ID
     * @return string Version cache key
     */
    private static function getVersionKey(int $ownerId): string
    {
        return "properties_list_version_{$ownerId}";
    }

    /**
     * Reset version for an owner (useful for testing or manual invalidation).
     * 
     * @param int $ownerId The tenant owner ID
     * @return void
     */
    public static function resetVersion(int $ownerId): void
    {
        $versionKey = self::getVersionKey($ownerId);
        Cache::forget($versionKey);
    }
}
