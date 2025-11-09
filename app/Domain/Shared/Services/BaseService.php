<?php

namespace App\Domain\Shared\Services;

use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
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
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

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
     * @param int $httpCode
     * @throws BusinessLogicException
     */
    protected function validateBusinessRule(
        bool $condition,
        string $message,
        string $code = 'BIZ_001',
        int $httpCode = 400
    ): void {
        if (!$condition) {
            throw new BusinessLogicException($message, $code, $httpCode);
        }
    }

    /**
     * Throw a business logic exception.
     *
     * @param string $message
     * @param string $code
     * @param int $httpCode
     * @return never
     * @throws BusinessLogicException
     */
    protected function fail(
        string $message,
        string $code = 'BIZ_001',
        int $httpCode = 400
    ): never {
        throw new BusinessLogicException($message, $code, $httpCode);
    }

    /**
     * Ensure a value exists or throw a not found exception.
     *
     * @template TValue
     * @param TValue|null $value
     * @param string $message
     * @return TValue
     * @throws ResourceNotFoundException
     */
    protected function ensureFound(mixed $value, string $message = 'Resource not found'): mixed
    {
        if ($value === null) {
            throw new ResourceNotFoundException($message);
        }

        return $value;
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

