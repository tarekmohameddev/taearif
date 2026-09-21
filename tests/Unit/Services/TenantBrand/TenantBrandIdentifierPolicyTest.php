<?php

namespace Tests\Unit\Services\TenantBrand;

use App\Services\TenantBrand\TenantBrandIdentifierPolicy;
use PHPUnit\Framework\TestCase;

class TenantBrandIdentifierPolicyTest extends TestCase
{
    /** @dataProvider validIdentifiers */
    public function test_valid_identifiers(string $identifier): void
    {
        $this->assertTrue((new TenantBrandIdentifierPolicy())->isValid($identifier));
    }

    public function validIdentifiers(): array
    {
        return [
            ['acme'],
            ['my-tenant'],
            ['shop.example.com'],
            ['www.example.net'],
            ['xn--mgbh0fb.xn--kgbechtv'],
        ];
    }

    /** @dataProvider invalidIdentifiers */
    public function test_invalid_identifiers(string $identifier): void
    {
        $this->assertFalse((new TenantBrandIdentifierPolicy())->isValid($identifier));
    }

    public function invalidIdentifiers(): array
    {
        return [
            [''],
            ['bad_name'],
            ['-leading'],
            ['trailing-'],
            ['double..dot'],
            ['space value'],
        ];
    }
}
