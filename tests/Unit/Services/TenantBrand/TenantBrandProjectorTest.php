<?php

namespace Tests\Unit\Services\TenantBrand;

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
            new TenantBrandContentHasher(['assets.example.com'], $this->root)
        );

        $this->assertSame([
            'logoUrl' => 'https://assets.example.com/logo.png',
            'inline' => null,
            'v' => hash('sha256', 'original-logo-bytes'),
        ], $projector->project('https://assets.example.com/logo.png'));
    }

    public function test_unverified_remote_original_is_not_exposed(): void
    {
        $projector = new TenantBrandProjector(new TenantBrandContentHasher([], $this->root));

        $this->assertSame(
            ['logoUrl' => null, 'inline' => null, 'v' => 'none'],
            $projector->project('https://untrusted.example/logo.png')
        );
    }
}
