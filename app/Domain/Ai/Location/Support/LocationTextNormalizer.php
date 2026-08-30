<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Support;

use App\Domain\Ai\Knowledge\ArabicNormalizer;

final class LocationTextNormalizer
{
    /**
     * Normalize a location phrase for lookup.
     *
     * This intentionally aligns with LocationLookup::normalizeName but reuses the
     * shared ArabicNormalizer and keeps it usable for bot runtime code.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            return null;
        }

        $text = ArabicNormalizer::normalizeForSearch($text);

        // Strip common prefixes (city/district/region markers)
        $text = (string) preg_replace('/^(ال|حي\s+|مدينه\s+|مدينة\s+|منطقه\s+|منطقة\s+|محافظه\s+|محافظة\s+)/u', '', $text);

        // Collapse whitespace
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return $text !== '' ? $text : null;
    }

    public static function hasDistrictMarker(string $raw): bool
    {
        return (bool) preg_match('/\bحي\b/u', $raw);
    }
}

