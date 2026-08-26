<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\Tools;

use App\Domain\Ai\Services\LocationResolver;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Services\Matching\PropertySearchService;
use App\Support\DTO\UnifiedRequest;
use Illuminate\Support\Facades\Log;

final class PropertySearchTool
{
    private const MAX_RESULTS = 5;

    /**
     * Maps LLM property_type tokens and Arabic aliases to api_user_categories IDs.
     * Multiple IDs mean "any of these subtypes" (e.g., apartment covers شقة + شقة في عمارة).
     * The second element is the broad property_type for user_properties.property_type (or null to skip).
     *
     * Category IDs (from api_user_categories seeder):
     *   1=فيلا  2=شقة في برج  3=شقة في عمارة  4=أرض  7=استراحة
     *   8=محل تجاري  9=مكتب  12=مبنى  13=دور في فيلا  15=عمارة  18=شقة
     *
     * @var array<string, array{0: int[], 1: string|null}>
     */
    private const CATEGORY_MAP = [
        // English tokens (what LLM returns)
        'apartment'    => [[3, 18, 2], 'residential'],
        'villa'        => [[1], 'residential'],
        'townhouse'    => [[1], 'residential'],
        'land'         => [[4], null],
        'office'       => [[9], 'commercial'],
        'warehouse'    => [[12], 'commercial'],
        'building'     => [[15], null],
        'duplex'       => [[13], 'residential'],
        'rest_house'   => [[7], 'residential'],
        // Arabic tokens (from MessageFactExtractor or direct LLM output)
        'شقة'          => [[3, 18, 2], 'residential'],
        'شقه'          => [[3, 18, 2], 'residential'],
        'فيلا'         => [[1], 'residential'],
        'فله'          => [[1], 'residential'],
        'فلة'          => [[1], 'residential'],
        'تاون هاوس'    => [[1], 'residential'],
        'تاونهاوس'     => [[1], 'residential'],
        'أرض'          => [[4], null],
        'ارض'          => [[4], null],
        'عمارة'        => [[15], null],
        'عمارة سكنية'  => [[15], 'residential'],
        'عمارة تجارية' => [[15], 'commercial'],
        'مكتب'         => [[9], 'commercial'],
        'محل'          => [[8], 'commercial'],
        'محل تجاري'    => [[8], 'commercial'],
        'مستودع'       => [[12], 'commercial'],
        'دوبلكس'       => [[13], 'residential'],
        'دور'          => [[13], 'residential'],
        'استراحة'      => [[7], 'residential'],
        'قصر'          => [[1], 'residential'],
        'مزرعة'        => [[4], 'agricultural'],
    ];

    public function __construct(
        private readonly PropertySearchService $searchService,
        private readonly LocationResolver $locationResolver,
    ) {}

    /**
     * Tool definition for LLM function calling.
     */
    public static function definition(): array
    {
        return [
            'type'     => 'function',
            'function' => [
                'name'        => 'search_properties',
                'description' => 'ابحث عن عقارات متاحة للبيع أو الإيجار حسب المعايير المحددة. استخدم هذه الأداة عند سؤال العميل عن عقارات أو وحدات.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'location'      => ['type' => 'string',  'description' => 'اسم المدينة أو الحي أو الشارع بالعربية'],
                        'purpose'       => ['type' => 'string',  'enum' => ['sale', 'rent'], 'description' => 'بيع أو إيجار'],
                        'property_type' => [
                            'type'        => 'string',
                            'description' => 'نوع العقار. القيم المقبولة: apartment (شقة), villa (فيلا), building (عمارة), land (أرض), office (مكتب), warehouse (مستودع), duplex (دوبلكس), rest_house (استراحة)',
                        ],
                        'bedrooms'      => ['type' => 'integer', 'description' => 'عدد غرف النوم المطلوبة'],
                        'budget_max'    => ['type' => 'number',  'description' => 'الحد الأقصى للميزانية بالريال السعودي'],
                        'budget_min'    => ['type' => 'number',  'description' => 'الحد الأدنى للميزانية بالريال السعودي'],
                    ],
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Resolve an Arabic or English property type token to category IDs and broad property_type.
     *
     * @return array{0: int[], 1: string|null}
     */
    public static function resolveTypeToCategories(string $typeToken): array
    {
        $key = mb_strtolower(trim($typeToken));
        return self::CATEGORY_MAP[$key] ?? [[], null];
    }

    /**
     * Execute the property search and return formatted results.
     *
     * @return array{results: array, count: int, has_more: bool, clarification_needed: bool, clarification_question: string|null}
     */
    public function execute(int $tenantId, array $params): array
    {
        $locationText     = trim((string) ($params['location'] ?? ''));
        $locationResolved = null;

        if ($locationText !== '') {
            $locationResolved = $this->locationResolver->resolve($tenantId, $locationText);
            $needsClarification = !empty($locationResolved['needs_clarification']);
            $confidence = isset($locationResolved['confidence']) ? (int) $locationResolved['confidence'] : 0;

            // Preserve legacy behavior: only stop the search when clarification is needed
            // AND confidence is low. When confidence is acceptable, proceed with the
            // resolved IDs to avoid abandoning valid property searches.
            if ($needsClarification && $confidence < 40) {
                return [
                    'results'                => [],
                    'count'                  => 0,
                    'has_more'               => false,
                    'clarification_needed'   => true,
                    'clarification_question' => $locationResolved['clarification_question'] ?? 'في أي مدينة أو حي تبحث؟',
                    'location_relaxed'       => false,
                    'relax_scope'            => 'none',
                    'requested_city_id'      => null,
                    'requested_district_id'  => null,
                    'requested_city'         => null,
                    'requested_district'     => null,
                    'requested_location'     => null,
                ];
            }
        }

        $request = $this->buildUnifiedRequest($tenantId, $params, $locationResolved, $locationText);

        try {
            $query = $this->searchService->buildBotQuery($request);
            $total = (clone $query)->count();

            // Load all language variants — tenants use per-user language IDs
            // (not global language_id=1). mapProperties() picks the best row.
            $properties = $query
                ->with('contents')
                ->orderByDesc('featured')
                ->orderByDesc('id')
                ->limit(self::MAX_RESULTS + 1)
                ->get();

            $hasMore    = $properties->count() > self::MAX_RESULTS;
            $properties = $properties->take(self::MAX_RESULTS);

            $requestedCityId = $request->cityId ? (int) $request->cityId : null;
            $requestedDistrictId = $request->districtId ? (int) $request->districtId : null;
            $requestedCity = $request->cityName ?: null;
            $requestedDistrict = $request->districtName ?: null;
            // Prefer the customer's original location text for disclosure labels —
            // LocationResolver sometimes maps a bare district to the wrong city.
            $requestedLocation = $locationText !== ''
                ? $locationText
                : trim(implode(' ', array_filter([$requestedDistrict, $requestedCity])));

            $results = $this->mapProperties($properties, requestedCityId: $requestedCityId, requestedDistrictId: $requestedDistrictId);
            $locationRelaxed = false;
            $relaxScope = 'none'; // none|district|city

            // Stepwise relaxation:
            // 1) relax district (keep city) if district constraint yields 0
            // 2) relax city (and district) if still 0
            if (empty($results) && ($request->cityId || $request->districtId)) {
                if ($request->districtId && $request->cityId) {
                    $relaxed = clone $request;
                    $relaxed->districtId = null;
                    $relaxed->districtName = null;

                    $relaxedQuery = $this->searchService->buildBotQuery($relaxed);
                    $relaxedTotal = (clone $relaxedQuery)->count();
                    $relaxedProps = $relaxedQuery
                        ->with('contents')
                        ->orderByDesc('featured')
                        ->orderByDesc('id')
                        ->limit(self::MAX_RESULTS + 1)
                        ->get();

                    $hasMore    = $relaxedProps->count() > self::MAX_RESULTS;
                    $relaxedProps = $relaxedProps->take(self::MAX_RESULTS);
                    $results = $this->mapProperties($relaxedProps, relaxed: true, requestedCityId: $requestedCityId, requestedDistrictId: null);
                    $total   = $relaxedTotal;
                    if ($results !== []) {
                        $relaxScope = 'district';
                    }
                }

                if (empty($results) && $request->cityId) {
                    $relaxed = clone $request;
                    $relaxed->cityId = null;
                    $relaxed->districtId = null;
                    $relaxed->cityName = null;
                    $relaxed->districtName = null;

                    $relaxedQuery = $this->searchService->buildBotQuery($relaxed);
                    $relaxedTotal = (clone $relaxedQuery)->count();
                    $relaxedProps = $relaxedQuery
                        ->with('contents')
                        ->orderByDesc('featured')
                        ->orderByDesc('id')
                        ->limit(self::MAX_RESULTS + 1)
                        ->get();

                    $hasMore    = $relaxedProps->count() > self::MAX_RESULTS;
                    $relaxedProps = $relaxedProps->take(self::MAX_RESULTS);
                    $results = $this->mapProperties($relaxedProps, relaxed: true, requestedCityId: null, requestedDistrictId: null);
                    $total   = $relaxedTotal;
                    if ($results !== []) {
                        $relaxScope = 'city';
                    }
                }

                // Evidence-based disclosure: only relax when none of the returned listings
                // matches the requested scope.
                if ($relaxScope !== 'none' && $results !== [] && $requestedCityId !== null) {
                    $inScope = array_filter($results, static fn (array $r): bool => (int) ($r['city_id'] ?? 0) === $requestedCityId);
                    $locationRelaxed = empty($inScope);
                    if (!$locationRelaxed) {
                        $relaxScope = 'none';
                    }
                } else {
                    $locationRelaxed = $relaxScope !== 'none' && $results !== [];
                }
            }

            // If no results, relax bedrooms constraint and retry once
            if (empty($results) && !empty($params['bedrooms'])) {
                $request->bedrooms = null;
                $relaxedQuery      = $this->searchService->buildBotQuery($request);
                $relaxedProps      = $relaxedQuery
                    ->with('contents')
                    ->orderByDesc('featured')
                    ->limit(self::MAX_RESULTS)
                    ->get();
                $results = $this->mapProperties($relaxedProps, relaxed: true, requestedCityId: $requestedCityId, requestedDistrictId: $requestedDistrictId);
            }

            return [
                'results'                => $results,
                'count'                  => count($results),
                'has_more'               => $hasMore,
                'total_available'        => $total,
                'clarification_needed'   => false,
                'clarification_question' => null,
                'location_relaxed'       => $locationRelaxed,
                'relax_scope'            => $relaxScope,
                'requested_city_id'      => $requestedCityId,
                'requested_district_id'  => $requestedDistrictId,
                'requested_city'         => $requestedCity,
                'requested_district'     => $requestedDistrict,
                'requested_location'     => $requestedLocation !== '' ? $requestedLocation : null,
            ];
        } catch (\Throwable $e) {
            Log::error('bot.property_search.error', ['error' => $e->getMessage(), 'tenant' => $tenantId]);

            return [
                'results'                => [],
                'count'                  => 0,
                'has_more'               => false,
                'clarification_needed'   => false,
                'clarification_question' => null,
                'location_relaxed'       => false,
                'relax_scope'            => 'none',
                'requested_city_id'      => null,
                'requested_district_id'  => null,
                'requested_city'         => null,
                'requested_district'     => null,
                'requested_location'     => null,
                'error'                  => 'search_failed',
            ];
        }
    }

    private function buildUnifiedRequest(int $tenantId, array $params, ?array $locationResolved, string $locationText): UnifiedRequest
    {
        $request         = new UnifiedRequest('whatsapp', 0);
        $request->userId = $tenantId;
        $request->source = 'whatsapp';
        $request->message = $locationText;

        if ($locationResolved !== null) {
            $request->cityId      = $locationResolved['city_id'];
            $request->regionId    = $locationResolved['region_id'];
            $request->districtId  = $locationResolved['district_id'];
            $request->cityName    = $locationResolved['city_name'];
            $request->districtName = $locationResolved['district_name'];
        }

        if (!empty($params['purpose'])) {
            $request->purpose = $params['purpose'] === 'rent' ? 'rent' : 'sale';
        }

        // Map property_type token → category_id array + broad property_type
        if (!empty($params['property_type'])) {
            [$categoryIds, $broadType] = self::resolveTypeToCategories($params['property_type']);
            if (!empty($categoryIds)) {
                $request->categoryIds = $categoryIds;
            }
            if ($broadType !== null) {
                $request->propertyType = $broadType;
            }
        }

        if (!empty($params['bedrooms'])) {
            $request->bedrooms = (int) $params['bedrooms'];
        }
        if (!empty($params['budget_max'])) {
            $request->budgetTo = (float) $params['budget_max'];
        }
        if (!empty($params['budget_min'])) {
            $request->budgetFrom = (float) $params['budget_min'];
        }

        return $request;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Property> $properties
     */
    private function mapProperties(
        mixed $properties,
        bool $relaxed = false,
        ?int $requestedCityId = null,
        ?int $requestedDistrictId = null,
    ): array
    {
        $rows = $properties->map(function (Property $p) use ($relaxed, $requestedCityId, $requestedDistrictId) {
            $content = $this->pickBestContent($p, $requestedCityId, $requestedDistrictId);
            $title = trim((string) ($content?->title ?? ''));
            $address = trim((string) ($content?->address ?? ''));
            $cityId = isset($content?->city_id) ? (int) $content->city_id : null;
            $districtId = isset($content?->state_id) ? (int) $content->state_id : null;
            $row = [
                'id'            => $p->id,
                'title'         => $title !== '' ? $title : ('عقار #' . $p->id),
                'address'       => $address,
                'price'         => (float) $p->price, // authoritative from user_properties.price
                'currency'      => 'SAR',
                'purpose'       => $p->purpose,
                'property_type' => $p->property_type,
                'bedrooms'      => $p->beds,
                'bathrooms'     => $p->bath,
                'area_sqm'      => $p->area,
                'image_url'     => $p->featured_image_url ?? null,
                'city_id'       => $cityId,
                'district_id'   => $districtId,
            ];
            if ($relaxed) {
                $row['_relaxed'] = true;
            }
            return $row;
        })->filter(function (array $row): bool {
            // Drop junk inventory rows that confuse the LLM / has_results_guard
            // (e.g. title "ااا", address "بيسبشسب", area 0).
            return ! $this->isJunkPropertyRow($row);
        })->values();

        // Hydrate canonical city/district names for reply grounding (max 5 rows).
        $cityIds = array_values(array_unique(array_filter($rows->pluck('city_id')->all(), static fn ($v) => is_numeric($v))));
        $districtIds = array_values(array_unique(array_filter($rows->pluck('district_id')->all(), static fn ($v) => is_numeric($v))));

        $cityMap = $cityIds !== []
            ? UserCity::query()->whereIn('id', $cityIds)->get(['id', 'name_ar'])->pluck('name_ar', 'id')->toArray()
            : [];
        $districtMap = $districtIds !== []
            ? UserDistrict::query()->whereIn('id', $districtIds)->get(['id', 'name_ar'])->pluck('name_ar', 'id')->toArray()
            : [];

        return $rows->map(static function (array $row) use ($cityMap, $districtMap): array {
            $cid = isset($row['city_id']) ? (int) $row['city_id'] : 0;
            $did = isset($row['district_id']) ? (int) $row['district_id'] : 0;
            $cityName = $cid > 0 ? (string) ($cityMap[$cid] ?? '') : '';
            $districtName = $did > 0 ? (string) ($districtMap[$did] ?? '') : '';

            // ReplyRenderer reads `city` field (not city_id) for {{p:ID|city}}.
            if ($cityName !== '') {
                $row['city'] = $cityName;
            }
            if ($districtName !== '') {
                $row['district'] = $districtName;
            }

            return $row;
        })->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isJunkPropertyRow(array $row): bool
    {
        $title = trim((string) ($row['title'] ?? ''));
        $address = trim((string) ($row['address'] ?? ''));
        $price = (float) ($row['price'] ?? 0);
        $area = (float) ($row['area_sqm'] ?? 0);

        // Fallback "عقار #id" alone with no price/area/address is unusable
        $isFallbackTitle = (bool) preg_match('/^عقار\s*#\d+$/u', $title);

        // Repeated-character nonsense (ااا، ببب، xxx)
        $isGibberish = static function (string $s): bool {
            if ($s === '' || mb_strlen($s) < 2) {
                return $s !== '';
            }
            if (mb_strlen($s) <= 6 && preg_match('/^(.)\1+$/u', $s)) {
                return true;
            }
            // Mostly non-letter noise
            return (bool) preg_match('/^[^\p{L}\d]{3,}$/u', $s);
        };

        if ($isGibberish($title) || ($title !== '' && $isGibberish($address) && $area <= 0 && $price <= 0)) {
            return true;
        }

        if ($isFallbackTitle && $address === '' && $area <= 0 && $price <= 0) {
            return true;
        }

        // Title is gibberish-like short string even if price exists
        if ($title !== '' && mb_strlen($title) <= 4 && $isGibberish($title)) {
            return true;
        }

        return false;
    }

    /**
     * Prefer a content row with a real title. Never assume language_id=1 —
     * tenant languages are per-user (e.g. 1983) and hardcoding drops titles.
     */
    private function pickBestContent(Property $p, ?int $requestedCityId = null, ?int $requestedDistrictId = null): mixed
    {
        $contents = $p->contents;
        if ($contents === null || $contents->isEmpty()) {
            return null;
        }

        // Prefer the row that matches requested district/city so address/title are grounded.
        if ($requestedDistrictId !== null) {
            $match = $contents->first(static function ($c) use ($requestedDistrictId): bool {
                return (int) ($c->state_id ?? 0) === $requestedDistrictId;
            });
            if ($match !== null) {
                return $match;
            }
        }
        if ($requestedCityId !== null) {
            $match = $contents->first(static function ($c) use ($requestedCityId): bool {
                return (int) ($c->city_id ?? 0) === $requestedCityId;
            });
            if ($match !== null) {
                return $match;
            }
        }

        $withTitle = $contents->first(static function ($c): bool {
            return trim((string) ($c->title ?? '')) !== '';
        });

        return $withTitle ?? $contents->first();
    }
}
