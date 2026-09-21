<?php

namespace Tests\Unit\Services\TenantBrand;

use App\Services\TenantBrand\TenantBrandPlaceholderPolicy;
use PHPUnit\Framework\TestCase;

class TenantBrandPlaceholderPolicyTest extends TestCase
{
    /** @dataProvider placeholders */
    public function test_placeholder_rules(string $value): void
    {
        $this->assertTrue((new TenantBrandPlaceholderPolicy())->isPlaceholder($value));
    }

    public function placeholders(): array
    {
        return [
            'empty' => ['   '],
            'default path' => ['/images/main/logo.png'],
            'placehold substring' => ['https://cdn.test/PLACEHOLDER/logo.png'],
            'via placeholder substring' => ['https://via.placeholder.com/logo.png'],
            'api placeholder path' => ['https://cdn.test/api/placeholder/logo'],
            'plain x dimensions' => ['logo 200x55.png'],
            'multiplication dimensions' => ['logo 200×55.png'],
            'spaced dimensions' => ['logo 200 x 55.png'],
            'encoded dimensions' => ['logo-200%20x%2055.png'],
        ];
    }

    public function test_normal_logo_is_not_a_placeholder(): void
    {
        $this->assertFalse((new TenantBrandPlaceholderPolicy())->isPlaceholder('https://cdn.test/logo.png'));
    }
}
