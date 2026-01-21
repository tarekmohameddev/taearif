<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseVersionService
{
    /**
     * Cache key for MySQL version
     */
    private const CACHE_KEY = 'mysql_version';
    
    /**
     * Cache TTL in seconds (24 hours - version rarely changes)
     */
    private const CACHE_TTL = 86400;

    /**
     * Get MySQL version string
     * Cached for 24 hours to avoid repeated queries
     *
     * @return string
     */
    public static function getVersion(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                $result = DB::select('SELECT VERSION() as version');
                return $result[0]->version ?? '';
            } catch (\Exception $e) {
                Log::warning('Failed to get MySQL version', ['error' => $e->getMessage()]);
                return '';
            }
        });
    }

    /**
     * Check if MySQL version is >= specified version
     *
     * @param string $minVersion Minimum version (e.g., '5.6.0', '8.0.0')
     * @return bool
     */
    public static function isVersionAtLeast(string $minVersion): bool
    {
        $version = self::getVersion();
        if (empty($version)) {
            return false;
        }
        return version_compare($version, $minVersion, '>=');
    }

    /**
     * Check if MySQL version is 5.6.0 or higher
     *
     * @return bool
     */
    public static function isMysql56Plus(): bool
    {
        return self::isVersionAtLeast('5.6.0');
    }

    /**
     * Check if MySQL version is 8.0.0 or higher
     *
     * @return bool
     */
    public static function isMysql80Plus(): bool
    {
        return self::isVersionAtLeast('8.0.0');
    }

    /**
     * Clear the cached version (useful for testing or after MySQL upgrade)
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
