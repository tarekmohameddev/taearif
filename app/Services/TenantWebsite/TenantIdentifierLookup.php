<?php

namespace App\Services\TenantWebsite;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;

final class TenantIdentifierLookup
{
    public function __construct(private TenantIdentifierNormalizer $normalizer)
    {
    }

    public function normalize(string $identifier): string
    {
        return $this->normalizer->normalize($identifier);
    }

    public function find(string $identifier): ?User
    {
        $normalized = $this->normalize($identifier);
        $tenant = User::where('username', $normalized)->first();

        if ($tenant) {
            return $tenant;
        }

        return ApiDomainSetting::servable()
            ->where('custom_name', $normalized)
            ->first()
            ?->user;
    }
}
