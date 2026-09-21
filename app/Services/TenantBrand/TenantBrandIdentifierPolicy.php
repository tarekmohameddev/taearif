<?php

namespace App\Services\TenantBrand;

final class TenantBrandIdentifierPolicy
{
    public function isValid(string $identifier): bool
    {
        return preg_match(
            '/^(?=.{1,253}$)[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$/',
            $identifier
        ) === 1;
    }
}
