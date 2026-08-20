<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Leads;

/**
 * Detects the standard portal-lead template message (aqar.fm, bayut, etc.)
 * and extracts structured fields.
 *
 * Standard aqar.fm mobile template (Arabic):
 *   "السلام عليكم أرغب في التواصل مع المعلن على تطبيق عقار بخصوص الإعلان:
 *    شقة للإيجار في شارع الموازيني, حي الواحة, مدينة جدة, منطقة مكة المكرمة
 *    بسعر 32000.00 ريال
 *    https://sa.aqar.fm/ad/6633737/ar?a_id=7e8"
 *
 * These messages are NOT requests for human agents. The customer is a buyer
 * who wants to inquire about a specific property listing.
 */
final class PortalLeadParser
{
    private const PORTAL_DOMAINS = [
        'aqar'            => ['sa.aqar.fm', 'aqar.fm'],
        'bayut'           => ['www.bayut.sa', 'bayut.sa', 'bayut.com'],
        'property_finder' => ['propertyfinder.ae', 'propertyfinder.sa'],
        'wasalt'          => ['wasalt.com'],
        'opensooq'        => ['opensooq.com'],
        'dubizzle'        => ['dubizzle.com', 'dubizzle.sa'],
    ];

    private const PURPOSE_MAP = [
        'للإيجار' => 'rent',
        'للبيع'   => 'sale',
    ];

    private const ARABIC_PROPERTY_TYPES = [
        'شقة' => 'apartment', 'شقه' => 'apartment',
        'فيلا' => 'villa', 'فله' => 'villa', 'فلة' => 'villa',
        'أرض' => 'land', 'ارض' => 'land',
        'عمارة' => 'building', 'عماره' => 'building',
        'مكتب' => 'office',
        'مستودع' => 'warehouse',
        'دوبلكس' => 'duplex', 'دبلكس' => 'duplex',
        'فندق' => 'hotel',
        'مصنع' => 'factory',
        'محل' => 'shop',
        'شاليه' => 'chalet',
        'استراحة' => 'rest_house',
    ];

    /**
     * @return array{
     *     is_portal_lead: bool,
     *     platform: string,
     *     ad_url: string|null,
     *     ad_id: string|null,
     *     property_type_ar: string|null,
     *     property_type_db: string|null,
     *     purpose: string|null,
     *     city: string|null,
     *     district: string|null,
     *     street: string|null,
     *     price: float|null,
     * }
     */
    public function parse(string $text): array
    {
        $empty = [
            'is_portal_lead'  => false,
            'platform'        => '',
            'ad_url'          => null,
            'ad_id'           => null,
            'property_type_ar'=> null,
            'property_type_db'=> null,
            'purpose'         => null,
            'city'            => null,
            'district'        => null,
            'street'          => null,
            'price'           => null,
        ];

        if (!str_contains($text, 'أرغب في التواصل مع المعلن') &&
            !str_contains($text, 'في التواصل مع المعلن')) {
            return $empty;
        }

        $result = array_merge($empty, ['is_portal_lead' => true]);

        // Extract URL
        if (preg_match('/https?:\/\/[^\s\n]+/u', $text, $urlMatch)) {
            $url               = $urlMatch[0];
            $result['ad_url']  = $url;
            $result['platform']= $this->detectPlatform($url);
            $result['ad_id']   = $this->extractAdId($url);
        }

        // Extract the listing description after "بخصوص الإعلان:"
        $listing = null;
        if (preg_match('/بخصوص الإعلان[:\s]+(.+?)(?:\n|https?:|$)/us', $text, $m)) {
            $listing = trim(preg_replace('/https?:\/\/\S+/u', '', $m[1]) ?? $m[1]);
        }

        if ($listing !== null && $listing !== '') {
            $this->parseListing($listing, $result);
        }

        return $result;
    }

    /** True when the text is a portal lead template (buyer, not seller/human request). */
    public function isPortalLead(string $text): bool
    {
        return $this->parse($text)['is_portal_lead'];
    }

    private function parseListing(string $listing, array &$result): void
    {
        // Purpose
        foreach (self::PURPOSE_MAP as $ar => $en) {
            if (str_contains($listing, $ar)) {
                $result['purpose'] = $en;
                break;
            }
        }

        // Property type (first token before purpose keyword)
        if (preg_match('/^([^\s،,\n]+)\s+(?:للإيجار|للبيع)/u', $listing, $m)) {
            $ar                      = trim($m[1]);
            $result['property_type_ar'] = $ar;
            $result['property_type_db'] = self::ARABIC_PROPERTY_TYPES[$ar] ?? $ar;
        }

        // Price
        if (preg_match('/بسعر\s*([\d.,]+)/u', $listing, $m)) {
            $result['price'] = (float) str_replace(',', '', $m[1]);
        }

        // Location segment: everything between "في " and "بسعر"
        if (preg_match('/في\s+(.+?)(?:\s+بسعر|$)/us', $listing, $m)) {
            $this->parseLocationString($m[1], $result);
        }
    }

    private function parseLocationString(string $loc, array &$result): void
    {
        $parts = array_map('trim', explode(',', $loc));
        foreach ($parts as $part) {
            if (preg_match('/^(?:شارع|ش\.)\s+(.+)/u', $part, $m)) {
                $result['street'] ??= $m[1];
            } elseif (preg_match('/^حي\s+(.+)/u', $part, $m)) {
                $result['district'] ??= $m[1];
            } elseif (preg_match('/^مدينة\s+(.+)/u', $part, $m)) {
                $result['city'] ??= $m[1];
            }
            // "منطقة" = region — skip
        }
    }

    private function detectPlatform(string $url): string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        foreach (self::PORTAL_DOMAINS as $platform => $domains) {
            foreach ($domains as $domain) {
                if (str_contains($host, $domain)) {
                    return $platform;
                }
            }
        }
        return 'custom';
    }

    private function extractAdId(string $url): ?string
    {
        if (preg_match('~/ad/(\d+)~', $url, $m)) {
            return $m[1];
        }
        if (preg_match('~/property[/-](?:id-)?(\d+)~i', $url, $m)) {
            return $m[1];
        }
        // Query param ?id=... or &id=...
        $query = parse_url($url, PHP_URL_QUERY) ?? '';
        parse_str($query, $params);
        if (isset($params['id']) && ctype_digit((string) $params['id'])) {
            return (string) $params['id'];
        }
        return null;
    }
}
