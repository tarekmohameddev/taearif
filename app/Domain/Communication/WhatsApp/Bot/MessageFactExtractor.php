<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;

/**
 * Deterministic (no-LLM) extractor that parses Arabic message text for
 * structured search facts: budget, property type, location, bedrooms, purpose.
 *
 * Results are keyed to match WaConversationAiState->facts field names:
 *   budget_max, budget_min, type, city, district, bedrooms, intent (rent|sale)
 *
 * Extraction runs before slot-fill and context building so that facts mentioned
 * in the current message are visible to the search and slot-fill on this turn.
 */
final class MessageFactExtractor
{
    /**
     * Extract facts from one or more Arabic message strings.
     * Pass the most recent N messages (oldest first), the last one being the trigger.
     *
     * @param  string[] $messages
     * @return array<string, mixed>  Partial facts; only keys with detected values are set.
     */
    public static function extract(array $messages): array
    {
        $combined = implode(' ', $messages);

        $facts = [];

        $budget = self::extractBudget($combined);
        if ($budget !== null) {
            if (isset($budget['max'])) { $facts['budget_max'] = $budget['max']; }
            if (isset($budget['min'])) { $facts['budget_min'] = $budget['min']; }
        }

        $type = self::extractPropertyType($combined);
        if ($type !== null) {
            $facts['type'] = $type;
        }

        $location = self::extractLocation($combined);
        if ($location['city'] !== null)     { $facts['city']     = $location['city']; }
        if ($location['district'] !== null) { $facts['district'] = $location['district']; }

        $bedrooms = self::extractBedrooms($combined);
        if ($bedrooms !== null) {
            $facts['bedrooms'] = $bedrooms;
        }

        $purpose = self::extractPurpose($combined);
        if ($purpose !== null) {
            $facts['intent'] = $purpose;
        }

        return $facts;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extract budget from Arabic text.
     * Handles: "7 مليون", "700 ألف", "مليون ونص", "ميزانيتي 2 مليون", "بحدود 500000"
     *
     * @return array{max?: float, min?: float}|null
     */
    private static function extractBudget(string $text): ?array
    {
        $result = [];

        $text = self::normalizeArabicIndicDigits($text);
        $text = self::stripUrls($text);

        // Remove phone-like / ID-like long digit sequences so they don't get mistaken for budget.
        $text = preg_replace('/\+?\d[\d\s\-]{8,}\d/u', ' ', $text);

        $isMonthlyContext = (bool) preg_match('/(?:شهري|بالشهري|شهريا|الإيجار|الايجار|للإيجار|للايجار)/u', $text);

        // Prefer explicit price contexts (ignore discount numbers like "خصم 90 ألف")
        if (preg_match_all('/(?:السعر|سعر(?:ها|ه)?)\s*[:：]?\s*(\d{1,3}(?:,\d{3})+|\d+(?:\.\d+)?)/u', $text, $m)) {
            $candidates = array_map(fn ($v) => (float) str_replace(',', '', $v), $m[1]);
            if (! empty($candidates)) {
                $result['max'] = max($candidates);
                return $result;
            }
        }

        // "أقل من مليون"
        if (preg_match('/(?:اقل|أقل)\s+من\s+مليون/u', $text)) {
            return ['max' => 1_000_000.0];
        }

        // "مليونين" / "مليونين ونص"
        if (preg_match('/مليونين\s*(?:ونص|ونصف)?/u', $text, $m)) {
            $val = 2.0;
            if (str_contains($m[0], 'ونص') || str_contains($m[0], 'ونصف')) {
                $val += 0.5;
            }
            return ['max' => $val * 1_000_000];
        }

        // Range: "2500-3000" or "2500 – 3000" (common for monthly rent)
        if (preg_match('/\b(\d{2,7})\s*[-–]\s*(\d{2,7})\b/u', $text, $m)) {
            $min = (float) $m[1];
            $max = (float) $m[2];
            if ($min > 0 && $max > 0) {
                $result['min'] = min($min, $max);
                $result['max'] = max($min, $max);
                return $result;
            }
        }

        // Detect "من X إلى Y" or "بين X و Y" ranges for millions
        if (preg_match('/(?:من|بين)\s*(\d+(?:\.\d+)?)\s*(?:مليون)?\s*(?:إلى|الى|و)\s*(\d+(?:\.\d+)?)\s*مليون/u', $text, $m)) {
            return [
                'min' => (float) $m[1] * 1_000_000,
                'max' => (float) $m[2] * 1_000_000,
            ];
        }

        // Pattern: number + مليون (with optional fraction like ونص / ونصف)
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ونص|ونصف)?\s*مليون/u', $text, $m)) {
            $val = (float) $m[1];
            if (str_contains($m[0], 'ونص') || str_contains($m[0], 'ونصف')) {
                $val += 0.5;
            }
            return ['max' => $val * 1_000_000];
        }

        // Pattern: مليون ونص (without leading digit → assumes 1)
        if (preg_match('/(?<!\d)\s*مليون\s*(?:ونص|ونصف)/u', $text)) {
            return ['max' => 1_500_000.0];
        }

        // Pattern: number + ألف / الف
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ألف|الف)/u', $text, $m)) {
            return ['max' => (float) $m[1] * 1_000];
        }

        // Short budgets like "ميزانيتي حول 650" often mean 650k (unless monthly context)
        if (preg_match('/(?:ميزاني(?:تي)?|ميزانية|بميزانية|بحدود|حوالي|حول|بسعر)\s*(\d{2,5})(?!\d)/u', $text, $m)) {
            $n = (float) $m[1];
            if ($n > 0) {
                return ['max' => $isMonthlyContext ? $n : $n * 1_000];
            }
        }

        // Pattern: numbers with commas like 1,500,000 (only if budget context is present)
        if (preg_match('/\b(\d{1,3}(?:,\d{3})+)\b/u', $text, $m)
            && preg_match('/(?:ريال|سعر|السعر|ميزاني(?:تي)?|ميزانية)/u', $text)) {
            return ['max' => (float) str_replace(',', '', $m[1])];
        }

        // Pattern: raw large numbers (≥ 100,000) only when budget context exists
        if (preg_match('/\b(\d{6,})\b/u', $text, $m)
            && preg_match('/(?:ريال|سعر|السعر|ميزاني(?:تي)?|ميزانية)/u', $text)) {
            return ['max' => (float) $m[1]];
        }

        return empty($result) ? null : $result;
    }

    private static function normalizeArabicIndicDigits(string $text): string
    {
        $map = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];

        return strtr($text, $map);
    }

    private static function stripUrls(string $text): string
    {
        return (string) preg_replace('/https?:\/\/\S+/u', ' ', $text);
    }

    /**
     * Extract property type keyword from Arabic text.
     * Returns the Arabic keyword as-is so PropertySearchTool::resolveTypeToCategories()
     * can map it to category IDs.
     */
    private static function extractPropertyType(string $text): ?string
    {
        // Order matters: more specific phrases first
        $patterns = [
            'تاون هاوس'    => 'تاون هاوس',
            'تاونهاوس'     => 'تاون هاوس',
            'شقة في برج'   => 'شقة في برج',
            'شقة في عمارة' => 'شقة في عمارة',
            'محل تجاري'    => 'محل تجاري',
            'دور في فيلا'  => 'دوبلكس',
            'عمارة سكنية'  => 'عمارة سكنية',
            'عمارة تجارية' => 'عمارة تجارية',
            'عمارة'        => 'عمارة',
            'شقة'          => 'شقة',
            'شقه'          => 'شقة',
            'فيلا'         => 'فيلا',
            'فله'          => 'فيلا',
            'فلة'          => 'فيلا',
            'أرض'          => 'أرض',
            'ارض'          => 'أرض',
            'مكتب'         => 'مكتب',
            'محل'          => 'محل',
            'مستودع'       => 'مستودع',
            'دوبلكس'       => 'دوبلكس',
            'دور'          => 'دور',
            'استراحة'      => 'استراحة',
            'استراحه'      => 'استراحة',
            'قصر'          => 'قصر',
            'مزرعة'        => 'مزرعة',
            'مزرعه'        => 'مزرعة',
            'روف'          => 'شقة',   // roof apartment → apartment type
            'ملحق'         => 'شقة',   // annexe → apartment-class
        ];

        foreach ($patterns as $needle => $canonical) {
            if (mb_strpos($text, $needle) !== false) {
                return $canonical;
            }
        }

        return null;
    }

    /**
     * Extract city or district from Arabic text.
     * Patterns: "في الرياض", "حي النرجس", "شارع الملك عبدالعزيز", "بجدة"
     *
     * @return array{city: string|null, district: string|null}
     */
    private static function extractLocation(string $text): array
    {
        $city     = null;
        $district = null;

        // Explicit city mentions using city alias list
        $cityAliases = [
            'الرياض', 'رياض', 'جدة', 'جده', 'جدا',
            'مكة', 'مكه', 'الدمام', 'دمام', 'الخبر', 'خبر',
            'المدينة', 'المدينه', 'الطائف', 'طائف',
            'القصيم', 'الاحساء', 'احساء', 'الحساء',
            'تبوك', 'حائل', 'عسير', 'أبها', 'ابها',
            'نجران', 'جازان', 'جيزان', 'ينبع', 'العقيق',
        ];

        // Canonical city name map (alias → canonical)
        $cityCanonical = [
            'رياض'    => 'الرياض',
            'جده'     => 'جدة',
            'جدا'     => 'جدة',
            'مكه'     => 'مكة',
            'دمام'    => 'الدمام',
            'خبر'     => 'الخبر',
            'المدينه' => 'المدينة',
            'طائف'    => 'الطائف',
            'احساء'   => 'الاحساء',
            'الحساء'  => 'الاحساء',
            'ابها'    => 'أبها',
            'جيزان'   => 'جازان',
        ];

        foreach ($cityAliases as $alias) {
            if (mb_strpos($text, $alias) !== false) {
                $city = $cityCanonical[$alias] ?? $alias;
                break;
            }
        }

        // District: "حي X" pattern (allow multi-word names)
        if (preg_match('/حي\s+([\p{Arabic}][\p{Arabic}\s]{1,40}?)(?:\s*(?:و|في|،|,|\.|$))/u', $text, $m)) {
            $cap = trim($m[1]);
            $cap = preg_replace('/[^\p{Arabic}\s]+$/u', '', $cap);
            $cap = trim((string) $cap);
            if ($cap !== '') {
                $district = 'حي ' . $cap;
            }
        }

        // Street as district proxy: "شارع X Y Z" — capture up to 4 Arabic words
        if ($district === null && preg_match('/شارع\s+([\p{Arabic}][\p{Arabic}\s]{1,40}?)(?:\s*(?:و|في|،|,|\.|$))/u', $text, $m)) {
            $streetCapture = trim($m[1]);
            $streetCapture = preg_replace('/\s+(?:بميزانية|بميزانيت(?:ي)?|بسعر|بحدود|لميزانية)\b.*$/u', '', $streetCapture);
            $streetCapture = trim($streetCapture);
            if ($streetCapture !== '') {
                $district = 'شارع ' . $streetCapture;
            }
        } elseif ($district === null && preg_match('/شارع\s+([\p{Arabic}][\p{Arabic}\s]{1,40})/u', $text, $m)) {
            $streetCapture = trim($m[1]);
            $streetCapture = preg_replace('/\s+(?:بميزانية|بميزانيت(?:ي)?|بسعر|بحدود|لميزانية)\b.*$/u', '', $streetCapture);
            $streetCapture = trim($streetCapture);
            if ($streetCapture !== '') {
                $district = 'شارع ' . $streetCapture;
            }
        }

        // "في حي X" pattern
        if ($district === null && preg_match('/في\s+حي\s+([\p{Arabic}][\p{Arabic}\s]{1,40}?)(?:\s*(?:و|في|،|,|\.|$))/u', $text, $m)) {
            $cap = trim($m[1]);
            $cap = preg_replace('/[^\p{Arabic}\s]+$/u', '', $cap);
            $cap = trim((string) $cap);
            if ($cap !== '') {
                $district = 'حي ' . $cap;
            }
        }

        return ['city' => $city, 'district' => $district];
    }

    /**
     * Extract bedroom count from Arabic text.
     * Patterns: "3 غرف", "ثلاث غرف", "4 غرف نوم", "أربع غرف"
     */
    private static function extractBedrooms(string $text): ?int
    {
        $text = self::normalizeArabicIndicDigits($text);

        // Digit + غرف
        if (preg_match('/(\d+)\s*غرف/u', $text, $m)) {
            return (int) $m[1];
        }

        // Arabic number words
        $words = [
            'غرفة واحدة' => 1, 'غرفه واحده' => 1,
            'غرفتين' => 2, 'غرفتان' => 2,
            'ثلاث غرف' => 3, 'ثلاثة غرف' => 3,
            'أربع غرف' => 4, 'اربع غرف' => 4, 'أربعة غرف' => 4,
            'خمس غرف'  => 5, 'خمسة غرف'  => 5,
            'ست غرف'   => 6, 'ستة غرف'   => 6,
        ];

        foreach ($words as $phrase => $count) {
            if (mb_strpos($text, $phrase) !== false) {
                return $count;
            }
        }

        return null;
    }

    /**
     * Extract purpose (rent or sale) from Arabic text.
     */
    private static function extractPurpose(string $text): ?string
    {
        $rentKeywords = ['إيجار', 'ايجار', 'للإيجار', 'للايجار', 'بالإيجار', 'بالايجار', 'تأجير'];
        $saleKeywords = ['بيع', 'للبيع', 'شراء', 'أشتري', 'اشتري', 'تمليك'];

        foreach ($rentKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) { return 'rent'; }
        }
        foreach ($saleKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) { return 'sale'; }
        }

        return null;
    }

    /**
     * Determine if extracted facts are strong enough to force property_search intent.
     * Returns true if type, budget, or bedrooms were found (any is sufficient).
     */
    public static function hasSearchSignals(array $extractedFacts): bool
    {
        return isset($extractedFacts['type'])
            || isset($extractedFacts['budget_max'])
            || isset($extractedFacts['budget_min'])
            || isset($extractedFacts['bedrooms']);
    }
}
