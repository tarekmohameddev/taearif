<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot\Tools;

use App\Domain\Ai\Services\LocationResolver;
use App\Models\User\RealestateManagement\Property;
use App\Services\Matching\PropertySearchService;
use App\Support\DTO\UnifiedRequest;
use Illuminate\Support\Facades\Log;

final class PropertySearchTool
{
    private const MAX_RESULTS = 5;

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
                        'location'      => ['type' => 'string',  'description' => 'اسم المدينة أو الحي بالعربية'],
                        'purpose'       => ['type' => 'string',  'enum' => ['sale', 'rent'], 'description' => 'بيع أو إيجار'],
                        'property_type' => ['type' => 'string',  'description' => 'نوع العقار: apartment, villa, office, land, warehouse'],
                        'bedrooms'      => ['type' => 'integer', 'description' => 'عدد غرف النوم المطلوبة'],
                        'budget_max'    => ['type' => 'number',  'description' => 'الحد الأقصى للميزانية بالريال'],
                        'budget_min'    => ['type' => 'number',  'description' => 'الحد الأدنى للميزانية بالريال'],
                    ],
                    'required' => [],
                ],
            ],
        ];
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
            if ($locationResolved['needs_clarification'] && $locationResolved['confidence'] < 40) {
                return [
                    'results'                => [],
                    'count'                  => 0,
                    'has_more'               => false,
                    'clarification_needed'   => true,
                    'clarification_question' => $locationResolved['clarification_question'],
                ];
            }
        }

        $request = $this->buildUnifiedRequest($tenantId, $params, $locationResolved, $locationText);

        try {
            $query = $this->searchService->buildBotQuery($request);
            $total = (clone $query)->count();

            $properties = $query
                ->with(['contents' => fn ($q) => $q->where('language_id', 1)])
                ->orderByDesc('featured')
                ->orderByDesc('id')
                ->limit(self::MAX_RESULTS + 1)
                ->get();

            $hasMore    = $properties->count() > self::MAX_RESULTS;
            $properties = $properties->take(self::MAX_RESULTS);

            $results = $this->mapProperties($properties);

            // If no results, relax bedrooms constraint and retry once
            if (empty($results) && !empty($params['bedrooms'])) {
                $request->bedrooms = null;
                $relaxedQuery      = $this->searchService->buildBotQuery($request);
                $relaxedProps      = $relaxedQuery
                    ->with(['contents' => fn ($q) => $q->where('language_id', 1)])
                    ->orderByDesc('featured')
                    ->limit(self::MAX_RESULTS)
                    ->get();
                $results = $this->mapProperties($relaxedProps, relaxed: true);
            }

            return [
                'results'                => $results,
                'count'                  => count($results),
                'has_more'               => $hasMore,
                'total_available'        => $total,
                'clarification_needed'   => false,
                'clarification_question' => null,
            ];
        } catch (\Throwable $e) {
            Log::error('bot.property_search.error', ['error' => $e->getMessage(), 'tenant' => $tenantId]);

            return [
                'results'                => [],
                'count'                  => 0,
                'has_more'               => false,
                'clarification_needed'   => false,
                'clarification_question' => null,
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
        if (!empty($params['property_type'])) {
            $request->propertyType = $params['property_type'];
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
    private function mapProperties(mixed $properties, bool $relaxed = false): array
    {
        return $properties->map(function (Property $p) use ($relaxed) {
            $content = $p->contents->first();
            $row = [
                'id'            => $p->id,
                'title'         => $content?->title ?? ('عقار #' . $p->id),
                'address'       => $content?->address ?? '',
                'price'         => (float) $p->price, // authoritative from user_properties.price
                'currency'      => 'SAR',
                'purpose'       => $p->purpose,
                'property_type' => $p->property_type,
                'bedrooms'      => $p->beds,
                'bathrooms'     => $p->bath,
                'area_sqm'      => $p->area,
                'image_url'     => $p->featured_image_url ?? null,
            ];
            if ($relaxed) {
                $row['_relaxed'] = true;
            }
            return $row;
        })->values()->all();
    }
}
