<?php

namespace App\Services\TenantBrand;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class TenantBrandRefreshService
{
    public function __construct(private TenantBrandPayloadBuilder $payloadBuilder)
    {
    }

    /** @param array<string, object|null> $overrides */
    public function refreshSafely(int $tenantId, array $overrides = []): void
    {
        try {
            $this->refresh($tenantId, $overrides);
        } catch (Throwable $exception) {
            $deletionError = null;
            try {
                $this->invalidate($tenantId);
            } catch (Throwable $deleteException) {
                $deletionError = $deleteException->getMessage();
            }

            Log::error('Tenant brand refresh failed.', [
                'tenant_id' => $tenantId,
                'error' => $exception->getMessage(),
                'fallback_deletion_error' => $deletionError,
            ]);
        }
    }

    /** @param list<string> $identifiers */
    public function invalidateSafely(int $tenantId, array $identifiers = []): void
    {
        try {
            $this->invalidate($tenantId, $identifiers);
        } catch (Throwable $exception) {
            Log::error('Tenant brand invalidation failed.', [
                'tenant_id' => $tenantId,
                'identifiers' => $identifiers,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /** @param array<string, object|null> $overrides */
    private function refresh(int $tenantId, array $overrides): void
    {
        $redis = Redis::connection('cache');
        $aliases = $redis->smembers($this->metadataKey($tenantId));
        if ($aliases === []) {
            return;
        }

        $tenant = User::find($tenantId);
        if (! $tenant) {
            $this->invalidate($tenantId);

            return;
        }

        $json = json_encode(
            $this->payloadBuilder->buildWith($tenant, $overrides),
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $ttl = (int) config('tenant_brand.payload_ttl_seconds', 21600) + random_int(0, 300);
        $changed = [];

        foreach ($aliases as $alias) {
            if (! is_string($alias) || $alias === '') {
                continue;
            }
            if ($redis->get($this->payloadKey($alias)) !== $json) {
                $changed[] = $alias;
            }
        }

        if ($changed === []) {
            return;
        }

        $redis->transaction(function ($transaction) use ($changed, $json, $ttl) {
            foreach ($changed as $alias) {
                $transaction->setex($this->payloadKey($alias), $ttl, $json);
                $transaction->del($this->negativeKey($alias));
            }
        });
    }

    /** @param list<string> $extraIdentifiers */
    private function invalidate(int $tenantId, array $extraIdentifiers = []): void
    {
        $redis = Redis::connection('cache');
        $metadataKey = $this->metadataKey($tenantId);
        $aliases = array_values(array_unique(array_merge(
            array_filter($redis->smembers($metadataKey), 'is_string'),
            array_filter($extraIdentifiers)
        )));

        $redis->transaction(function ($transaction) use ($aliases, $metadataKey) {
            foreach ($aliases as $alias) {
                $transaction->del($this->payloadKey($alias));
                $transaction->del($this->negativeKey($alias));
            }
            $transaction->del($metadataKey);
        });
    }

    private function payloadKey(string $identifier): string
    {
        return 'brand:v1:' . $identifier;
    }

    private function negativeKey(string $identifier): string
    {
        return 'brand-negative:v1:' . $identifier;
    }

    private function metadataKey(int $tenantId): string
    {
        return 'brand-meta:v1:tenant:' . $tenantId;
    }
}
