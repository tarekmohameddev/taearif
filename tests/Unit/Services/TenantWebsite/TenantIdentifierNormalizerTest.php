<?php

namespace Tests\Unit\Services\TenantWebsite;

use App\Services\TenantWebsite\TenantIdentifierNormalizer;
use PHPUnit\Framework\TestCase;

class TenantIdentifierNormalizerTest extends TestCase
{
    /** @dataProvider identifiers */
    public function test_it_normalizes_get_tenant_and_brand_identifiers_identically(string $input, string $expected): void
    {
        $normalizer = new TenantIdentifierNormalizer();

        $this->assertSame($expected, $normalizer->normalize($input));
    }

    public function identifiers(): array
    {
        return [
            'mixed-case slug' => ['  AcMe  ', 'acme'],
            'www hostname' => ['WWW.Example.COM', 'example.com'],
            'trailing dot' => ['shop.example.com.', 'shop.example.com'],
            'port' => ['shop.example.com:8443', 'shop.example.com'],
            'protocol, www, dot, port and slash' => ['HTTPS://WWW.Example.COM.:443/', 'example.com'],
            'apex' => ['example.com', 'example.com'],
        ];
    }
}
