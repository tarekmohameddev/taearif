<?php

namespace Tests\Unit\Services\TenantBrand;

use App\Contracts\TenantBrandVariantRepository;
use App\Services\TenantBrand\TenantBrandContentHasher;
use App\Services\TenantBrand\TenantBrandProjector;
use PHPUnit\Framework\TestCase;

class TenantBrandProjectorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tenant-brand-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->root);
        parent::tearDown();
    }

    public function test_v_is_sha256_of_original_bytes(): void
    {
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'logo.png', 'original-logo-bytes');
        $projector = new TenantBrandProjector(
            new TenantBrandContentHasher(['assets.example.com'], $this->root),
            $this->variants()
        );

        $this->assertSame([
            'logoUrl' => 'https://assets.example.com/logo.png',
            'inline' => null,
            'v' => hash('sha256', 'original-logo-bytes'),
        ], $projector->project('https://assets.example.com/logo.png'));
    }

    public function test_remote_original_is_exposed_until_variant_is_ready(): void
    {
        $projector = new TenantBrandProjector(
            new TenantBrandContentHasher([], $this->root),
            $this->variants()
        );

        $url = 'https://untrusted.example/logo.png';

        $this->assertSame([
            'logoUrl' => $url,
            'inline' => null,
            'v' => 'original-' . hash('sha256', $url),
        ], $projector->project($url));
    }

    public function test_ready_variant_replaces_interim_original(): void
    {
        $url = 'https://assets.example.com/logo.png';
        file_put_contents($this->root . DIRECTORY_SEPARATOR . 'logo.png', 'source');
        $variant = [
            'logoUrl' => 'https://assets.example.com/storage/tenant-brand/hash.webp',
            'inline' => 'data:image/webp;base64,YQ==',
            'v' => str_repeat('a', 64),
        ];
        $projector = new TenantBrandProjector(
            new TenantBrandContentHasher(['assets.example.com'], $this->root),
            $this->variants($variant)
        );

        $this->assertSame($variant, $projector->project($url));
    }

    private function variants(?array $ready = null): TenantBrandVariantRepository
    {
        return new class($ready) implements TenantBrandVariantRepository {
            public function __construct(private ?array $ready)
            {
            }

            public function findReady(string $sourceUrl, ?string $sourceContentHash): ?array
            {
                return $this->ready;
            }
        };
    }
}
