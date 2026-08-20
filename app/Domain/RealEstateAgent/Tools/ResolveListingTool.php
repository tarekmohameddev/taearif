<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Models\PropertyExternalLink;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a portal listing URL or extracted lead fields to a property
 * in the tenant's own inventory via property_external_links.
 *
 * Match order:
 *  1. Exact URL match against property_external_links.url
 *  2. Normalised URL (strip query-string / a_id suffix)
 *  3. Ad ID from URL path (/ad/N/) against stored URLs
 *  4. Price ±2 % + city + property_type fallback
 *
 * Returns the full property record including FAQs and external links so the
 * model can answer customer questions without calling further tools.
 */
final class ResolveListingTool implements AgentTool
{
    private const ARABIC_TYPE_MAP = [
        'شقة' => 'apartment', 'شقه' => 'apartment',
        'فيلا' => 'villa', 'فله' => 'villa', 'فلة' => 'villa',
        'أرض' => 'land', 'ارض' => 'land',
        'عمارة' => 'building', 'عماره' => 'building',
        'مكتب' => 'office',
        'مستودع' => 'warehouse',
        'دوبلكس' => 'duplex', 'دبلكس' => 'duplex',
    ];

    public function name(): string
    {
        return 'resolve_listing';
    }

    public function schema(): array
    {
        return [
            'name'        => 'resolve_listing',
            'description' => 'حوّل رابط إعلان خارجي (عقار.fm / بيوت / وصلت...) أو معطيات إعلان إلى عقار في مخزون المكتب. استخدم هذه الأداة عند وصول رابط إعلان من مشتري محتمل.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'ad_url'        => ['type' => 'string', 'description' => 'رابط الإعلان الخارجي كاملاً'],
                    'ad_id'         => ['type' => 'string', 'description' => 'رقم الإعلان على المنصة إن كان متاحاً'],
                    'property_type' => ['type' => 'string', 'description' => 'نوع العقار من الإعلان (شقة / فيلا / أرض...)'],
                    'purpose'       => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'الغرض من الإعلان'],
                    'city'          => ['type' => 'string', 'description' => 'المدينة من الإعلان'],
                    'district'      => ['type' => 'string', 'description' => 'الحي من الإعلان'],
                    'price'         => ['type' => 'number', 'description' => 'السعر من الإعلان'],
                ],
                'required'   => [],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        $adUrl        = trim((string) ($args['ad_url'] ?? ''));
        $adId         = trim((string) ($args['ad_id'] ?? ''));
        $price        = isset($args['price']) && is_numeric($args['price']) ? (float) $args['price'] : null;
        $city         = trim((string) ($args['city'] ?? ''));
        $district     = trim((string) ($args['district'] ?? ''));
        $propertyType = trim((string) ($args['property_type'] ?? ''));
        $purpose      = trim((string) ($args['purpose'] ?? ''));

        try {
            // 1 & 2: URL-based matches
            if ($adUrl !== '') {
                $found = $this->matchByUrl($adUrl, $tenantId);
                if ($found !== null) {
                    return $found;
                }

                // Try to extract ad ID from the URL path when not supplied
                if ($adId === '' && preg_match('~/ad/(\d+)~', $adUrl, $m)) {
                    $adId = $m[1];
                }
            }

            // 3: Ad ID match
            if ($adId !== '') {
                $found = $this->matchByAdId($adId, $tenantId);
                if ($found !== null) {
                    return $found;
                }
            }

            // 4: Price + attribute fallback
            if ($price !== null && $price > 0) {
                $found = $this->matchByPriceAndAttributes($price, $purpose, $propertyType, $city, $district, $tenantId);
                if ($found !== null) {
                    return $found;
                }
            }

            return ['found' => false, 'reason' => 'no_match'];

        } catch (\Throwable $e) {
            Log::warning('agent.tool.resolve_listing.error', [
                'tenant_id' => $tenantId,
                'error'     => $e->getMessage(),
            ]);
            return ['found' => false, 'reason' => 'error'];
        }
    }

    // ─── Match strategies ─────────────────────────────────────────────────────

    private function matchByUrl(string $adUrl, int $tenantId): ?array
    {
        // Exact match
        $link = PropertyExternalLink::where('user_id', $tenantId)
            ->where('url', $adUrl)
            ->where('active', true)
            ->first();

        if ($link) {
            return $this->buildResult($link->property_id, $tenantId, 'exact_url');
        }

        // Normalised URL (strip query string)
        $normalUrl = strtok($adUrl, '?');
        if ($normalUrl && $normalUrl !== $adUrl) {
            $link = PropertyExternalLink::where('user_id', $tenantId)
                ->where('active', true)
                ->where('url', 'like', $normalUrl . '%')
                ->first();

            if ($link) {
                return $this->buildResult($link->property_id, $tenantId, 'normalized_url');
            }
        }

        return null;
    }

    private function matchByAdId(string $adId, int $tenantId): ?array
    {
        $link = PropertyExternalLink::where('user_id', $tenantId)
            ->where('active', true)
            ->where(function ($q) use ($adId) {
                $q->where('url', 'like', "%/ad/{$adId}/%")
                  ->orWhere('url', 'like', "%/ad/{$adId}?%")
                  ->orWhere('url', 'like', "%/{$adId}/%");
            })
            ->first();

        if ($link) {
            return $this->buildResult($link->property_id, $tenantId, 'ad_id');
        }

        return null;
    }

    private function matchByPriceAndAttributes(
        float  $price,
        string $purpose,
        string $propertyType,
        string $city,
        string $district,
        int    $tenantId,
    ): ?array {
        $query = Property::with('contents')
            ->where('user_id', $tenantId)
            ->where('status', 'published')
            ->whereBetween('price', [$price * 0.98, $price * 1.02]);

        if ($purpose !== '') {
            $query->where('purpose', $purpose);
        }

        if ($propertyType !== '') {
            $dbType = self::ARABIC_TYPE_MAP[$propertyType] ?? $propertyType;
            $query->where('property_type', $dbType);
        }

        $matches = $query->limit(10)->get();

        if ($matches->isEmpty()) {
            return null;
        }

        // Narrow by city / district from content.address
        if ($city !== '' || $district !== '') {
            $narrowed = $matches->first(function (Property $p) use ($city, $district) {
                $address      = mb_strtolower((string) ($p->contents?->first()?->address ?? ''));
                $cityMatch    = $city === '' || str_contains($address, mb_strtolower($city));
                $distMatch    = $district === '' || str_contains($address, mb_strtolower($district));
                return $cityMatch && $distMatch;
            });
            if ($narrowed) {
                return $this->buildResult($narrowed->id, $tenantId, 'price_location');
            }
        }

        // If exactly one price match, assume it
        if ($matches->count() === 1) {
            return $this->buildResult($matches->first()->id, $tenantId, 'price_only');
        }

        return null;
    }

    // ─── Result builder ───────────────────────────────────────────────────────

    private function buildResult(int $propertyId, int $tenantId, string $matchType): array
    {
        $property = Property::with([
            'contents',
            'externalLinks' => fn ($q) => $q->where('active', true),
        ])
            ->where('user_id', $tenantId)
            ->find($propertyId);

        if (!$property) {
            return ['found' => false, 'reason' => 'property_not_found'];
        }

        $content = $property->contents
            ?->first(fn ($c) => trim((string) ($c->title ?? '')) !== '')
            ?? $property->contents?->first();

        $externalLinks = ($property->externalLinks ?? collect())->map(fn ($l) => [
            'platform' => $l->platform,
            'url'      => $l->url,
            'label'    => $l->label,
        ])->all();

        $faqs = is_array($property->faqs) ? $property->faqs : [];

        return [
            'found'          => true,
            'match_type'     => $matchType,
            'id'             => $property->id,
            'title'          => trim((string) ($content?->title ?? '')) ?: ('عقار #' . $property->id),
            'address'        => trim((string) ($content?->address ?? '')),
            'description'    => trim((string) ($content?->description ?? '')),
            'price'          => (float) $property->price,
            'currency'       => 'SAR',
            'purpose'        => $property->purpose,
            'property_type'  => $property->property_type,
            'bedrooms'       => $property->beds,
            'bathrooms'      => $property->bath,
            'area_sqm'       => $property->area,
            'floor'          => $property->floor ?? null,
            'furnished'      => $property->furnished ?? null,
            'image_url'      => $property->featured_image_url ?? null,
            'video_url'      => $property->video_url ?? null,
            'virtual_tour'   => $property->virtual_tour ?? null,
            'faqs'           => $faqs,
            'external_links' => $externalLinks,
        ];
    }
}
