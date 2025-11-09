<?php

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Base Service Class
 *
 * Provides common functionality for all domain services
 * Handles transactions, caching, and common business logic patterns
 */
abstract class BaseService
{
    /**
     * Execute operation within a database transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }

    /**
     * Backwards-compatible alias for executeInTransaction.
     *
     * @deprecated Use executeInTransaction instead.
     *
     * @param callable $callback
     * @return mixed
     * @throws \Throwable
     */
    protected function transaction(callable $callback): mixed
    {
        return $this->executeInTransaction($callback);
    }

    /**
     * Cache the result of a callback
     *
     * @param string $key
     * @param int $ttl Time to live in seconds
     * @param callable $callback
     * @return mixed
     */
    protected function cache(string $key, int $ttl, callable $callback): mixed
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Clear cache by key
     *
     * @param string $key
     * @return bool
     */
    protected function clearCache(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache by tag
     *
     * @param string|array $tags
     * @return bool
     */
    protected function clearCacheByTag(string|array $tags): bool
    {
        return Cache::tags($tags)->flush();
    }

    /**
     * Generate cache key
     *
     * @param string $prefix
     * @param mixed ...$params
     * @return string
     */
    protected function generateCacheKey(string $prefix, mixed ...$params): string
    {
        return $prefix . '.' . md5(serialize($params));
    }

    /**
     * Validate business rule
     *
     * @param bool $condition
     * @param string $message
     * @param string $code
     * @throws \Exception
     */
    protected function validateBusinessRule(
        bool $condition,
        string $message,
        string $code = 'BIZ_001'
    ): void {
        if (!$condition) {
            throw new \Exception($message);
        }
    }

    /**
     * Log service activity
     *
     * @param string $action
     * @param array $context
     * @return void
     */
    protected function logActivity(string $action, array $context = []): void
    {
        logger()->info("[{$action}]", $context);
    }
}

