<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Ai\Knowledge\ArabicNormalizer;
use Tests\TestCase;

final class ArabicNormalizerTest extends TestCase
{
    public function test_removes_diacritics(): void
    {
        $input    = 'مُرْحَبًا بِكَ';
        $expected = 'مرحبا بك';
        $this->assertSame($expected, ArabicNormalizer::normalize($input));
    }

    public function test_normalizes_alef_variants(): void
    {
        $this->assertSame('الرياض', ArabicNormalizer::normalize('الرياض'));
        $this->assertSame('ابو', ArabicNormalizer::normalize('أبو'));
        $this->assertSame('امل', ArabicNormalizer::normalize('أمل'));
        $this->assertSame('اسماء', ArabicNormalizer::normalize('آسماء'));
    }

    public function test_normalizes_taa_marbuta_to_haa(): void
    {
        $this->assertSame('شقه', ArabicNormalizer::normalize('شقة'));
        $this->assertSame('فيلا', ArabicNormalizer::normalize('فيلا'));
    }

    public function test_normalizes_ya_variants(): void
    {
        $this->assertSame('ريال', ArabicNormalizer::normalize('ريال'));
        // alef maqsura → ya
        $input = 'على'; // alef maqsura at end
        $this->assertSame('علي', ArabicNormalizer::normalize($input));
    }

    public function test_collapses_whitespace(): void
    {
        // normalize() also applies char map: أ→ا, ة→ه
        $this->assertSame('اريد شقه', ArabicNormalizer::normalize('أريد   شقة'));
    }

    public function test_for_search_lowercases(): void
    {
        $this->assertSame('riyadh', ArabicNormalizer::normalizeForSearch('Riyadh'));
    }
}
