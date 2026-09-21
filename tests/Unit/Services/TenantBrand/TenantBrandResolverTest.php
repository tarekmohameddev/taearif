<?php

namespace Tests\Unit\Services\TenantBrand;

use App\Services\TenantBrand\TenantBrandPlaceholderPolicy;
use App\Services\TenantBrand\TenantBrandResolver;
use App\Services\TenantBrand\TenantBrandUrlPolicy;
use PHPUnit\Framework\TestCase;

class TenantBrandResolverTest extends TestCase
{
    private TenantBrandResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new TenantBrandResolver(
            new TenantBrandPlaceholderPolicy(),
            new TenantBrandUrlPolicy(['cdn.allowlisted.io'])
        );
    }

    /** @dataProvider parityCases */
    public function test_frontend_parity_table(array $globals, ?string $branding, array $layout, ?string $expected): void
    {
        $this->assertSame($expected, $this->resolver->resolve($globals, $branding, $layout));
    }

    public function parityCases(): array
    {
        return [
            '1 empty image uses src' => [$this->header('  ', 'https://c.io/s.png'), null, [], 'https://c.io/s.png'],
            '2 placeholder image ignores src then uses branding' => [$this->header('/images/main/logo.png', 'https://c.io/s.png'), 'https://c.io/b.png', [], 'https://c.io/b.png'],
            '3 dimension placeholder uses branding' => [$this->header('https://c.io/x/200x55.png'), 'https://c.io/b.png', [], 'https://c.io/b.png'],
            '4 empty header uses branding' => [$this->header('', ''), 'https://c.io/b.png', [], 'https://c.io/b.png'],
            '5 header wins' => [$this->header('https://c.io/A.png'), 'https://c.io/B.png', $this->legacy('https://c.io/C.png'), 'https://c.io/A.png'],
            '6 legacy only' => [[], null, $this->legacy('https://c.io/old.png'), 'https://c.io/old.png'],
            '7 non-string image uses src' => [$this->header(12345, 'https://c.io/s.png'), null, [], 'https://c.io/s.png'],
            '8 object image uses branding' => [$this->header(['src' => 'x']), 'https://c.io/b.png', [], 'https://c.io/b.png'],
            '9 string header logo uses branding' => [['header' => ['logo' => 'not-an-object']], 'https://c.io/b.png', [], 'https://c.io/b.png'],
            '10 branding is trimmed' => [[], '  https://c.io/b.png  ', [], 'https://c.io/b.png'],
            '11 branding placeholder uses legacy' => [[], 'https://c.io/PLACEHOLDER/logo.png', $this->legacy('https://c.io/l.png'), 'https://c.io/l.png'],
            '12 dimension placeholder falls through' => [[], 'https://c.io/logo 200×55.png', [], null],
            '13 relative upload returns null without fallback' => [$this->header('/uploads/a.png'), 'https://c.io/b.png', [], null],
            '14 default relative logo returns null' => [$this->header('/logo.png'), null, [], null],
            '15 allowlisted http upgrades' => [$this->header('http://cdn.allowlisted.io/a.png'), null, [], 'https://cdn.allowlisted.io/a.png'],
            '16 other http returns null' => [$this->header('http://other.example/a.png'), null, [], null],
            '17 missing returns null' => [[], null, [], null],
        ];
    }

    public function test_protocol_relative_allowlisted_host_upgrades_to_https(): void
    {
        $this->assertSame(
            'https://cdn.allowlisted.io/a.png',
            $this->resolver->resolve($this->header('//cdn.allowlisted.io/a.png'), null, [])
        );
    }

    public function test_data_uri_is_rejected_without_fallthrough(): void
    {
        $this->assertNull($this->resolver->resolve(
            $this->header('data:image/png;base64,abc'),
            'https://c.io/b.png',
            []
        ));
    }

    private function header(mixed $image, mixed $src = null): array
    {
        return ['header' => ['logo' => ['image' => $image, 'src' => $src]]];
    }

    private function legacy(mixed $logo): array
    {
        return ['CustomBranding' => ['header' => ['logo' => $logo]]];
    }
}
