<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BuildingSlugTest extends TestCase
{
    public function test_make_slug_preserves_arabic_text(): void
    {
        if (! function_exists('make_slug')) {
            require_once dirname(__DIR__, 2) . '/app/Http/Helpers/Helper.php';
        }

        $slug = make_slug('برج السلام');

        $this->assertNotSame('', $slug);
        $this->assertStringContainsString('برج', $slug);
        $this->assertStringContainsString('السلام', $slug);
    }
}
