<?php

namespace App\Services\Vercel;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VercelDomainCache
{
    private const TTL_SECONDS = 300;

    private const FAILURE_TTL_SECONDS = 60;

    public function __construct(
        private readonly VercelDomainInventoryService $inventory
    ) {
    }

    public function cached(): ?array
    {
        if (! $this->inventory->client()->isConfigured()) {
            return null;
        }

        $cached = Cache::get($this->inventoryKey());

        if ($cached === false) {
            return null;
        }

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $snapshot = $this->inventory->buildSnapshot(fetchFresh: true);
            Cache::put($this->inventoryKey(), $snapshot, self::TTL_SECONDS);

            return $snapshot ?: null;
        } catch (\Throwable $exception) {
            Log::warning('Vercel inventory cache refresh failed', [
                'exception' => $exception->getMessage(),
            ]);

            Cache::put($this->inventoryKey(), false, self::FAILURE_TTL_SECONDS);

            return null;
        }
    }

    public function fresh(): array
    {
        $snapshot = $this->inventory->buildSnapshot(fetchFresh: true);
        Cache::put($this->inventoryKey(), $snapshot, self::TTL_SECONDS);
        Cache::put($this->capacityKey(), $snapshot['metrics'] ?? [], self::TTL_SECONDS);

        return $snapshot;
    }

    public function invalidate(): void
    {
        foreach ($this->allKeys() as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Invalidate scoped Vercel inventory caches and admin domain health counters.
     * Call after mutations or reconciliation fixes — not from model observers during bulk sync.
     */
    public function invalidateAdminCaches(): void
    {
        $this->invalidate();
        Cache::forget('admin.domain_health_counts');
    }

    public function inventoryKey(): string
    {
        return $this->keyPrefix() . ':inventory';
    }

    public function capacityKey(): string
    {
        return $this->keyPrefix() . ':capacity';
    }

    public function healthCountersKey(): string
    {
        return $this->keyPrefix() . ':health_counters';
    }

    public function mutationLockKey(): string
    {
        return $this->keyPrefix() . ':mutation';
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws LockTimeoutException
     */
    public function withMutationLock(callable $callback, int $seconds = 90, int $blockSeconds = 30): mixed
    {
        return Cache::lock($this->mutationLockKey(), $seconds)->block($blockSeconds, $callback);
    }

    public function reconciliationReportKey(): string
    {
        return $this->keyPrefix() . ':reconciliation';
    }

    public function keyPrefix(): string
    {
        $env = (string) config('app.env', 'production');
        $team = (string) (config('services.vercel.team_id') ?: 'none');
        $projectHash = substr(hash('sha256', (string) config('services.vercel.project_id')), 0, 12);

        return "vercel:{$env}:{$team}:{$projectHash}";
    }

    /**
     * @return list<string>
     */
    private function allKeys(): array
    {
        return array_values(array_unique(array_merge(
            [
                $this->inventoryKey(),
                $this->capacityKey(),
                $this->healthCountersKey(),
                $this->reconciliationReportKey(),
                $this->mutationLockKey(),
            ],
            $this->legacyKeys()
        )));
    }

    /**
     * @return list<string>
     */
    private function legacyKeys(): array
    {
        return [
            'vercel.project_domains',
            'vercel.project_domain_count',
            'vercel.project_domain_names',
        ];
    }
}
