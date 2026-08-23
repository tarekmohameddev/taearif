<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Ai\Knowledge\TextChunker;
use Tests\TestCase;

final class TextChunkerTest extends TestCase
{
    private TextChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new TextChunker(400, 80);
    }

    public function test_returns_empty_for_empty_text(): void
    {
        $this->assertSame([], $this->chunker->chunk(''));
    }

    public function test_returns_single_chunk_for_short_text(): void
    {
        $text = 'هذا نص قصير للاختبار.';
        $chunks = $this->chunker->chunk($text);
        $this->assertCount(1, $chunks);
        $this->assertSame($text, $chunks[0]);
    }

    public function test_splits_long_text_into_multiple_chunks(): void
    {
        $paragraph = str_repeat('هذه جملة طويلة تُستخدم في اختبار التقسيم. ', 20);
        $chunks = $this->chunker->chunk($paragraph);
        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_each_chunk_under_max_size(): void
    {
        $text = implode("\n\n", array_fill(0, 10, str_repeat('كلمة ', 50)));
        $chunks = $this->chunker->chunk($text);
        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(500, mb_strlen($chunk), "Chunk too long: " . mb_substr($chunk, 0, 50));
        }
    }

    public function test_filters_out_trivially_short_chunks(): void
    {
        $text = "محتوى قصير\n\nأ\n\nمحتوى طويل بما يكفي للاحتفاظ به في قاعدة المعرفة.";
        $chunks = $this->chunker->chunk($text);
        foreach ($chunks as $chunk) {
            $this->assertGreaterThan(20, mb_strlen($chunk));
        }
    }
}
