<?php

namespace Tests\Unit\Services\Analytics;

use App\Services\Analytics\PageviewService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PageviewServicePathNormalizationTest extends TestCase
{
    public function test_it_normalizes_internal_paths_without_changing_unicode(): void
    {
        $this->assertSame(
            '/ar/project/مشروع-1',
            PageviewService::normalizePath('/ar//project/مشروع-1/?utm_source=stage#details')
        );
    }

    public function test_it_rejects_external_and_protocol_relative_urls(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PageviewService::normalizePath('https://example.test/project/demo');
    }

    public function test_it_rejects_protocol_relative_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PageviewService::normalizePath('//example.test/project/demo');
    }

    public function test_it_rejects_control_characters_in_internal_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PageviewService::normalizePath("/ar/project/demo\0slug");
    }
}
