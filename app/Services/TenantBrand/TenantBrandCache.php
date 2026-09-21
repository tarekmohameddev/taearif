<?php

namespace App\Services\TenantBrand;

use App\Services\TenantWebsite\TenantIdentifierLookup;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

final class TenantBrandCache
{
    public function __construct(
        private TenantIdentifierLookup $tenantLookup,
        private TenantBrandPayloadBuilder $payloadBuilder
    ) {
    }

    public function get(string $normalizedIdentifier): TenantBrandCacheResult
    {
        $redis = Redis::connection('cache');
        $payload = $redis->get($this->payloadKey($normalizedIdentifier));
        if (is_string($payload)) {
            return new TenantBrandCacheResult(200, $payload);
        }

        if ($redis->get($this->negativeKey($normalizedIdentifier)) !== null) {
            return $this->notFound();
        }

        try {
            return Cache::store('redis')
                ->lock($this->lockKey($normalizedIdentifier), 10)
                ->block(2, fn () => $this->buildAndStore($normalizedIdentifier));
        } catch (LockTimeoutException) {
            $payload = $redis->get($this->payloadKey($normalizedIdentifier));

            return is_string($payload)
                ? new TenantBrandCacheResult(200, $payload)
                : $this->buildAndStore($normalizedIdentifier);
        }
    }

    public function buildUncached(string $normalizedIdentifier): TenantBrandCacheResult
    {
        $tenant = $this->tenantLookup->find($normalizedIdentifier);
        if (! $tenant) {
            return $this->notFound();
        }

        return new TenantBrandCacheResult(200, $this->encode($this->payloadBuilder->build($tenant)));
    }

    public function rebuild(string $normalizedIdentifier): TenantBrandCacheResult
    {
        $redis = Redis::connection('cache');
        $redis->del($this->payloadKey($normalizedIdentifier));
        $redis->del($this->negativeKey($normalizedIdentifier));

        return $this->get($normalizedIdentifier);
    }

    private function buildAndStore(string $normalizedIdentifier): TenantBrandCacheResult
    {
        $redis = Redis::connection('cache');
        $payload = $redis->get($this->payloadKey($normalizedIdentifier));
        if (is_string($payload)) {
            return new TenantBrandCacheResult(200, $payload);
        }

        $tenant = $this->tenantLookup->find($normalizedIdentifier);
        if (! $tenant) {
            $this->storeNegative($normalizedIdentifier);

            return $this->notFound();
        }

        $json = $this->encode($this->payloadBuilder->build($tenant));
        $ttl = (int) config('tenant_brand.payload_ttl_seconds', 21600) + random_int(0, 300);

        $redis->transaction(function ($transaction) use ($normalizedIdentifier, $tenant, $json, $ttl) {
            $transaction->setex($this->payloadKey($normalizedIdentifier), $ttl, $json);
            $transaction->sadd($this->metadataKey((int) $tenant->id), [$normalizedIdentifier]);
            $transaction->expire($this->metadataKey((int) $tenant->id), (int) config('tenant_brand.metadata_ttl_seconds', 2592000));
            $transaction->del($this->negativeKey($normalizedIdentifier));
        });

        return new TenantBrandCacheResult(200, $json);
    }

    private function storeNegative(string $identifier): void
    {
        $redis = Redis::connection('cache');
        $bucket = 'brand-negative-rate:v2:' . gmdate('YmdHi');
        $count = (int) $redis->incr($bucket);
        if ($count === 1) {
            $redis->expire($bucket, 120);
        }

        if ($count <= (int) config('tenant_brand.negative_max_per_minute', 10000)) {
            $redis->setex($this->negativeKey($identifier), 60, '1');
        }
    }

    private function encode(array $payload): string
    {
        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function notFound(): TenantBrandCacheResult
    {
        return new TenantBrandCacheResult(404, '{"message":"Tenant not found"}');
    }

    private function payloadKey(string $identifier): string
    {
        return 'brand:v2:' . $identifier;
    }

    private function negativeKey(string $identifier): string
    {
        return 'brand-negative:v2:' . $identifier;
    }

    private function metadataKey(int $tenantId): string
    {
        return 'brand-meta:v2:tenant:' . $tenantId;
    }

    private function lockKey(string $identifier): string
    {
        return 'brand-lock:v2:' . $identifier;
    }
}
