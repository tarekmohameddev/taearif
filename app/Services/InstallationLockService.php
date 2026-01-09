<?php

namespace App\Services;

use App\Exceptions\Installation\ConcurrentInstallationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Installation Lock Service
 *
 * Prevents concurrent installation attempts using distributed locks
 */
class InstallationLockService
{
    /**
     * Lock timeout in seconds
     */
    protected int $lockTimeout = 300; // 5 minutes

    /**
     * Acquire lock for installation
     *
     * @param int $userId
     * @param int $appId
     * @return string Lock key
     * @throws ConcurrentInstallationException
     */
    public function acquireLock(int $userId, int $appId): string
    {
        $lockKey = $this->getLockKey($userId, $appId);

        // Try to acquire lock
        $acquired = Cache::lock($lockKey, $this->lockTimeout)->get();

        if (!$acquired) {
            Log::warning('Concurrent installation attempt detected', [
                'user_id' => $userId,
                'app_id' => $appId,
                'lock_key' => $lockKey,
            ]);

            throw new ConcurrentInstallationException($userId, $appId);
        }

        Log::debug('Installation lock acquired', [
            'user_id' => $userId,
            'app_id' => $appId,
            'lock_key' => $lockKey,
        ]);

        return $lockKey;
    }

    /**
     * Release lock
     *
     * @param string $lockKey
     * @return void
     */
    public function releaseLock(string $lockKey): void
    {
        try {
            $lock = Cache::lock($lockKey);
            $lock->release();

            Log::debug('Installation lock released', [
                'lock_key' => $lockKey,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to release installation lock', [
                'lock_key' => $lockKey,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute callback with lock
     *
     * @param int $userId
     * @param int $appId
     * @param callable $callback
     * @return mixed
     * @throws ConcurrentInstallationException
     */
    public function withLock(int $userId, int $appId, callable $callback)
    {
        $lockKey = $this->acquireLock($userId, $appId);

        try {
            return $callback();
        } finally {
            $this->releaseLock($lockKey);
        }
    }

    /**
     * Get lock key for user and app
     *
     * @param int $userId
     * @param int $appId
     * @return string
     */
    protected function getLockKey(int $userId, int $appId): string
    {
        return "installation_lock:user_{$userId}:app_{$appId}";
    }

    /**
     * Check if lock exists
     *
     * @param int $userId
     * @param int $appId
     * @return bool
     */
    public function isLocked(int $userId, int $appId): bool
    {
        $lockKey = $this->getLockKey($userId, $appId);
        $lock = Cache::lock($lockKey);

        return !$lock->get();
    }
}

