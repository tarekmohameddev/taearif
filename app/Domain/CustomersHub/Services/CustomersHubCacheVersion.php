<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\Cache;

/**
 * CustomersHubCacheVersion
 *
 * Driver-agnostic cache invalidation for Customers Hub list/stats/count caches.
 *
 * These caches are parameterized by arbitrary filter/pagination combinations
 * (viewer id, viewed-at, unread ids, filters hash, limit/offset, ...), so
 * there is no way to enumerate and forget every possible key on write. The
 * cache driver in use (`file`, see .env CACHE_DRIVER) does not support cache
 * tags either, so tag-based flushing is not an option.
 *
 * Instead, every affected cache key embeds the tenant's current "version".
 * Bumping the version (on any create/update/delete of a UserPropertyRequest
 * or ApiCustomerInquiry) makes all previously cached entries for that tenant
 * unreachable on the very next read, regardless of filter/pagination
 * combination, without needing to enumerate keys. Existing TTLs are kept as
 * a safety net.
 */
class CustomersHubCacheVersion
{
    private const KEY_PREFIX = 'ch:cache_version:';

    /**
     * TTL for the version marker itself. Not meant to expire in normal
     * operation (versions are only ever bumped forward), but bounded so the
     * `file` cache driver doesn't accumulate stale entries forever.
     */
    private const VERSION_TTL_DAYS = 30;

    /**
     * Read the current cache version for a tenant. Defaults to 1 when unset
     * (no write-on-read, to avoid races between concurrent first reads).
     */
    public function getVersion(int $userId): int
    {
        return (int) (Cache::get(self::key($userId)) ?? 1);
    }

    /**
     * Invalidate all Customers Hub list/stats/count caches for a tenant by
     * bumping its version marker.
     *
     * Deliberately does not rely solely on Cache::increment(): on several
     * drivers/stores (e.g. the `array` store used in tests), incrementing a
     * missing key seeds it at the increment amount (1) — identical to the
     * implicit default returned by getVersion() for an absent key — so the
     * very first bump would be a no-op. Reading the current value first and
     * writing back current+1 guarantees the version always changes.
     */
    public function bump(int $userId): void
    {
        $key = self::key($userId);
        $current = (int) (Cache::get($key) ?? 1);

        Cache::put($key, $current + 1, now()->addDays(self::VERSION_TTL_DAYS));
    }

    private static function key(int $userId): string
    {
        return self::KEY_PREFIX . $userId;
    }
}
