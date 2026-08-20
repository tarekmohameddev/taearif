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

        // Detect "من X إلى Y" or "بين X و Y" or "حول X إلى Y" ranges for millions
        if (preg_match('/(?:من|بين|حول|حوالي)?\s*(\d+(?:\.\d+)?)\s*(?:مليون)?\s*(?:إلى|الى|و)\s*(\d+(?:\.\d+)?)\s*مليون/u', $text, $m)) {
            return [
                'min' => (float) $m[1] * 1_000_000,
                'max' => (float) $m[2] * 1_000_000,
            ];
        }

        // Pattern: "5 مليون و 500 ألف" / "3 مليون و200" — must beat bare "N مليون"
        if (preg_match('/(\d+(?:\.\d+)?)\s*مليون\s*و\s*(\d{2,4})\s*(?:ألف|الف)?/u', $text, $m)) {
            $base = (float) $m[1] * 1_000_000;
            $extra = (float) $m[2];
            if (preg_match('/مليون\s*و\s*' . preg_quote($m[2], '/') . '\s*(?:ألف|الف)/u', $text)
                || $extra >= 100
            ) {
                $base += $extra * 1_000;
            } else {
                $base += $extra * 100_000;
            }

            return ['max' => $base];
        }

        // Pattern: number + مليون (with optional fraction like ونص / ونصف)
        // Do not match when "مليون و…" compound follows (handled above).
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ونص|ونصف)?\s*مليون(?!\s*و)/u', $text, $m)) {
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

        // Pattern: "مليون و700" / "مليون و 700" → 1,700,000
        if (preg_match('/(?<!\d)\s*مليون\s*و\s*(\d{2,3})\b/u', $text, $m)) {
            $extra = (float) $m[1];
            // 700 → +700k; values < 100 treated as hundred-thousands (مليون و7 → 1.7M)
            $add = $extra >= 100 ? $extra * 1_000 : $extra * 100_000;
            return ['max' => 1_000_000.0 + $add];
        }

        // Pattern: number + ألف / الف
        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:ألف|الف)/u', $text, $m)) {
            return ['max' => (float) $m[1] * 1_000];
        }

        // Short budgets like "ميزانيتي حول 650" often mean 650k (unless monthly context).
        // NEVER treat area phrases ("حوالي 140 متر" / "مساحة حول 250م") as budget.
        $areaContext = (bool) preg_match('/(?:مساحة|المساحة|متر|م²|م2|\d\s*م\b)/u', $text);
        if (
            ! $areaContext
            && preg_match('/(?:ميزاني(?:تي)?|ميزانية|بميزانية|بحدود|حوالي|حول|بسعر)\s*(\d{2,5})(?!\d)/u', $text, $m)
        ) {
            $n = (float) $m[1];
            if ($n > 0) {
                return ['max' => $isMonthlyContext ? $n : $n * 1_000];
            }
        }

        // "حدي 2200 شهري" / "حدّي 2500" — monthly ceiling; store annualized for inventory match.
        if (preg_match('/(?:حدي|حدّي|حد(?:ي|ى))\s*(\d{3,5})(?!\d)/u', $text, $m)) {
            $n = (float) $m[1];
            if ($n > 0) {
                return ['max' => $isMonthlyContext ? $n * 12 : ($n < 10_000 ? $n * 1_000 : $n)];
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
        // Strip false-positive contexts before keyword scan:
        // - "كم شقة فيها" / "عدد الشقق" = asking about units in a listing, not switching type
        // - "وين المكتب؟" = asking for the company office, not office-property search
        // - "موقع العمارة؟" / "عنوان العمارة" = asking where the building is, not type=عمارة
        // - "أرضية" / "دور ارضي" = ground-floor adjective, not type=أرض
        $scrubbed = $text;
        $scrubbed = preg_replace(
            '/(?:(?<!\p{L})كم\s+(?:عدد\s+)?(?:الشقق|الشقة|شقة|شقه|شقق)|عدد\s+الشقق|(?:الشقق|شقق)\s+فيها)/u',
            ' ',
            $scrubbed
        );
        $scrubbed = preg_replace(
            '/(?:وين|فين|أين|اين|مكان|عنوان|موقع)\s+المكتب|المكتب\s*(?:\?|؟|فين|وين|فين)/u',
            ' ',
            $scrubbed
        );
        $scrubbed = preg_replace(
            '/(?:وين|فين|أين|اين|مكان|عنوان|موقع|رابط)\s+(?:ال)?(?:عمارة|عماره|المبنى|مبنى)/u',
            ' ',
            $scrubbed
        );
        // Ground-floor adjectives must not trigger أرض (substring/stem match on ارض).
        // Never strip شقة/شقه here — only the floor adjective.
        $scrubbed = preg_replace(
            '/(?<!\p{L})(?:أرضية|ارضية|أرضيه|ارضيه|أرضى|ارضى|أرضي|ارضي)(?!\p{L})|دور\s*(?:أرضي|ارضي|أرضى)/u',
            ' ',
            $scrubbed
        );

        // Order matters: more specific phrases first.
        // Short tokens like "دور" must be whole-word only — "بدور/ادور/تدور" are verbs.
        // Floor questions ("دور رابع") are NOT a property type.
        $patterns = [
            'تاون هاوس'    => 'تاون هاوس',
            'تاونهاوس'     => 'تاون هاوس',
            'شقة في برج'   => 'شقة في برج',
            'شقة في عمارة' => 'شقة في عمارة',
            'محل تجاري'    => 'محل تجاري',
            'دور في فيلا'  => 'دوبلكس',
            'عمارة سكنية'  => 'عمارة سكنية',
            'عمارة تجارية' => 'عمارة تجارية',
            'عماره سكنيه'  => 'عمارة سكنية',
            'عماره تجاريه' => 'عمارة تجارية',
            'عمارة'        => 'عمارة',
            'عماره'        => 'عمارة',
            'شقة'          => 'شقة',
            'شقه'          => 'شقة',
            'شقق'          => 'شقة',
            'غرفة'         => 'شقة',   // room rental → apartment-class search
            'غرفه'         => 'شقة',
            'فيلا'         => 'فيلا',
            'فله'          => 'فيلا',
            'فلة'          => 'فيلا',
            'فلل'          => 'فيلا',   // plural — must beat bare "قصر" in "فلل أو قصر"
            'أرض'          => 'أرض',
            'ارض'          => 'أرض',
            'مكتب'         => 'مكتب',
            'محل'          => 'محل',
            'مستودع'       => 'مستودع',
            'دوبلكس'       => 'دوبلكس',
            'استراحة'      => 'استراحة',
            'استراحه'      => 'استراحة',
            'قصر مصغر'     => 'فيلا',
            'قصر'          => 'قصر',
            'مزرعة'        => 'مزرعة',
            'مزرعه'        => 'مزرعة',
            'روف'          => 'شقة',   // roof apartment → apartment type
            'ملحق'         => 'شقة',   // annexe → apartment-class
        ];

        foreach ($patterns as $needle => $canonical) {
            // Whole-token for short ambiguous stems (ارض/أرض must not match ارضيه — already scrubbed)
            if (in_array($needle, ['أرض', 'ارض', 'دور'], true)) {
                if (preg_match('/(?<!\p{L})' . preg_quote($needle, '/') . '(?!\p{L})/u', $scrubbed)) {
                    return $canonical;
                }
                continue;
            }
            if (mb_strpos($scrubbed, $needle) !== false) {
                return $canonical;
            }
        }

        // Whole-word "دور" only when it means a floor-unit listing, not "دور رابع؟" questions.
        if (
            preg_match('/(?:^|[^\p{Arabic}])دور(?:[^\p{Arabic}]|$)/u', $scrubbed)
            && ! preg_match('/دور\s*(?:أول|اول|ثاني|ثالث|رابع|خامس|سادس|سابع|أرضي|ارضي|\d+)/u', $scrubbed)
        ) {
            return 'دور';
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
            'القصيم', 'بريدة', 'بريده', 'عنيزة', 'عنيزه', 'البكيرية', 'البكيريه', 'الرس',
            'الاحساء', 'احساء', 'الحساء',
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
            'بريده'   => 'بريدة',
            'عنيزه'   => 'عنيزة',
            'البكيريه'=> 'البكيرية',
        ];

        foreach ($cityAliases as $alias) {
            // Letter-boundary match — "خبر" must not match inside "خبرني",
            // but trailing ؟ / ! after "جده؟" is fine. Allow attached بـ (بجدة).
            if (preg_match('/(?<!\p{L})ب?' . preg_quote($alias, '/') . '(?!\p{L})/u', $text)) {
                $city = $cityCanonical[$alias] ?? $alias;
                break;
            }
        }

        // Riyadh landmark streets/roads imply city when none named explicitly.
        if ($city === null && preg_match(
            '/(?:طريق|شارع)\s*(?:الملك\s*)?سلمان|خالد\s*بن\s*الوليد|جنوب\s*(?:طريق\s*)?(?:الملك\s*)?سلمان|غرب\s*خالد/u',
            $text
        )) {
            $city = 'الرياض';
        }

        // District: "حي X" — take whole words until a stop token.
        // NEVER stop on the letter و inside a name (e.g. الروضة used to become الر).
        $district = self::extractHaiDistrict($text);

        // Street as district proxy: "شارع X Y Z"
        if ($district === null) {
            $district = self::extractStreetDistrict($text);
        }

        return ['city' => $city, 'district' => $district];
    }

    /**
     * Capture "حي <name>" stopping only on whole-word separators, never mid-word و.
     */
    private static function extractHaiDistrict(string $text): ?string
    {
        if (! preg_match('/(?:^|[^\p{Arabic}])حي\s+([\p{Arabic}]+(?:\s+[\p{Arabic}]+)*)/u', $text, $m)) {
            return null;
        }

        $raw = trim($m[1]);
        // Exclusion lists are not districts: "حي شمال ماعدا الوادي والندى..."
        if (preg_match('/(?:^|\s)(?:ماعدا|ما\s*عدا|إلا|الا|سوى|باستثناء)\b/u', $raw)) {
            return null;
        }

        $name = self::takeLocationWordsUntilStop($raw, maxWords: 4);
        return $name !== '' ? 'حي ' . $name : null;
    }

    /**
     * Capture "شارع <name>" and strip trailing budget markers.
     */
    private static function extractStreetDistrict(string $text): ?string
    {
        if (! preg_match('/شارع\s+([\p{Arabic}]+(?:\s+[\p{Arabic}]+)*)/u', $text, $m)) {
            return null;
        }

        $name = self::takeLocationWordsUntilStop(trim($m[1]), maxWords: 5);
        return $name !== '' ? 'شارع ' . $name : null;
    }

    /**
     * Keep leading Arabic location words until a conjunction / budget / city token.
     * For lists like "الروضة أو السلامة أو الفيصلية" keeps the first district only.
     */
    private static function takeLocationWordsUntilStop(string $capture, int $maxWords): string
    {
        $stopWords = [
            'أو', 'او', 'و', 'في', 'بميزانية', 'بميزانيت', 'بميزانتي',
            'بسعر', 'بحدود', 'لميزانية', 'ميزانيتي', 'ميزانية',
            'للايجار', 'للإيجار', 'للايجار', 'غرفتين', 'غرف', 'غرفة',
        ];

        // Also treat bare / بـ-prefixed city aliases as stop tokens
        $cityStops = [
            'الرياض', 'رياض', 'جدة', 'جده', 'جدا', 'مكة', 'مكه',
            'الدمام', 'دمام', 'الخبر', 'خبر', 'المدينة', 'المدينه',
            'الطائف', 'طائف', 'بريدة', 'بريده', 'عنيزة', 'عنيزه',
            'البكيرية', 'البكيريه', 'القصيم',
        ];

        $words = preg_split('/\s+/u', $capture, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kept  = [];

        foreach ($words as $word) {
            $bare = $word;
            // Strip leading بـ (بجدة → جدة) for city-stop check
            if (mb_strpos($bare, 'ب') === 0 && mb_strlen($bare) > 2) {
                $bare = mb_substr($bare, 1);
            }

            if (in_array($word, $stopWords, true) || in_array($bare, $cityStops, true)) {
                break;
            }

            // Budget-ish tokens that got glued on
            if (preg_match('/^(?:بميزانية|بميزانيت|بسعر|بحدود)/u', $word)) {
                break;
            }

            $kept[] = $word;
            if (count($kept) >= $maxWords) {
                break;
            }
        }

        return trim(implode(' ', $kept));
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
        // Yield / income questions about an investment listing must NOT flip intent to rent.
        // e.g. "كم تقريباً الإيجار السنوي؟" while discussing a building for sale.
        $isYieldQuestion = (bool) preg_match(
            '/(?:الإيجار|الايجار)\s*السنوي|عائد\s*(?:الإيجار|الايجار|الاستثمار)|كم\s+(?:تقريباً?\s+)?(?:الإيجار|الايجار)/u',
            $text
        );

        // Strong rent signals first (explicit looking-to-rent)
        $strongRent = ['للإيجار', 'للايجار', 'بالإيجار', 'بالايجار', 'تأجير', 'استئجار', 'أستأجر', 'استاجر'];
        foreach ($strongRent as $kw) {
            if (mb_strpos($text, $kw) !== false) {
                return 'rent';
            }
        }

        // Bare إيجار/ايجار — only when not a yield question about existing inventory
        if (! $isYieldQuestion) {
            foreach (['إيجار', 'ايجار'] as $kw) {
                if (mb_strpos($text, $kw) !== false) {
                    return 'rent';
                }
            }
        }

        $saleKeywords = ['للبيع', 'بيع', 'شراء', 'أشتري', 'اشتري', 'تمليك'];
        foreach ($saleKeywords as $kw) {
            if (mb_strpos($text, $kw) !== false) {
                return 'sale';
            }
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
