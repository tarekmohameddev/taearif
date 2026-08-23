<?php

declare(strict_types=1);

namespace App\Domain\Ai\Knowledge;

final class ArabicNormalizer
{
    private const CHAR_MAP = [
        'أ' => 'ا',
        'إ' => 'ا',
        'آ' => 'ا',
        'ٱ' => 'ا',
        'ى' => 'ي',
        'ئ' => 'ي',
        'ؤ' => 'و',
        'ة' => 'ه',
        'گ' => 'ك',
        'چ' => 'ج',
        'ژ' => 'ز',
        'ڤ' => 'ف',
        'پ' => 'ب',
    ];

    /**
     * Normalize Arabic text: unify alef variants, ta-marbuta → ha, strip diacritics.
     * Preserves original casing (no lowercasing).
     */
    public static function normalize(string $text): string
    {
        // Strip diacritics and tatweel
        $text = (string) preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text);
        // Character normalization
        $text = strtr($text, self::CHAR_MAP);
        // Collapse whitespace
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Normalize for fuzzy search: normalize + lowercase.
     * Also strips Arabic definite article "ال" from word beginnings for better matching.
     */
    public static function normalizeForSearch(string $text): string
    {
        $text = self::normalize($text);
        $text = mb_strtolower($text);
        return $text;
    }

    /**
     * Strip the Arabic definite article "ال" from the start of each word.
     * Used when matching phrases that may or may not carry the definite article.
     */
    public static function stripDefiniteArticle(string $text): string
    {
        // Remove leading "ال" from each whitespace-delimited token
        return (string) preg_replace('/\bال/u', '', $text);
    }
}
