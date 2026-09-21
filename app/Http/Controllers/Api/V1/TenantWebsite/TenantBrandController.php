<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Services\TenantBrand\TenantBrandCache;
use App\Services\TenantBrand\TenantBrandIdentifierPolicy;
use App\Services\TenantWebsite\TenantIdentifierLookup;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

final class TenantBrandController extends Controller
{
    public function __construct(
        private TenantIdentifierLookup $tenantLookup,
        private TenantBrandIdentifierPolicy $identifierPolicy,
        private TenantBrandCache $cache
    ) {
    }

    public function show(string $tenantId): Response
    {
        $identifier = $this->tenantLookup->normalize($tenantId);
        if (! $this->identifierPolicy->isValid($identifier)) {
            return $this->json('{"message":"Invalid tenant identifier"}', 422);
        }

        try {
            $result = $this->cache->get($identifier);
        } catch (Throwable $exception) {
            Log::warning('Tenant brand cache unavailable; using uncached projection.', [
                'identifier' => $identifier,
                'error' => $exception->getMessage(),
            ]);
            $result = $this->cache->buildUncached($identifier);
        }

        return $this->json($result->json, $result->status);
    }

    private function json(string $json, int $status): Response
    {
        return response($json, $status, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
