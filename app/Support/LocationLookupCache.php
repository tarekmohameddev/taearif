<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Cache keys for the public /api/cities and /api/districts lookups.
 *
 * The payloads are effectively static between runs of `import:cities-districts`,
 * but they are read on every tenant site load, so they are cached for a day.
 *
 * Keys carry a version stamp instead of being forgotten individually: /districts
 * is cached per city_id, so there are ~473 possible keys and no practical way to
 * enumerate them. Bumping the version orphans every key at once, and unlike a
 * Redis SCAN sweep it works the same on the file driver used outside production.
 */
class LocationLookupCache
{
    const VERSION_KEY = 'api:locations:version';
    const TTL_SECONDS = 86400;

    /**
     * @param  string  $bucket  "cities" or "districts"
     * @param  mixed   $scope   country_id / city_id, or null for the unfiltered list
     */
    public static function key(string $bucket, $scope = null): string
    {
        $scope = ($scope === null || $scope === '') ? 'all' : $scope;

        return 'api:locations:v' . self::version() . ':' . $bucket . ':' . $scope;
    }

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, function () {
            return 1;
        });
    }

    /**
     * Invalidate every cached cities/districts payload. Called after a sync writes.
     */
    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
