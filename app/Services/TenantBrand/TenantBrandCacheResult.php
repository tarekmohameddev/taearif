<?php

namespace App\Services\TenantBrand;

final class TenantBrandCacheResult
{
    public function __construct(public int $status, public string $json)
    {
    }
}
