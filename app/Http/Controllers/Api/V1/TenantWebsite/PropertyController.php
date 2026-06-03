<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Services\PropertyTranslationService;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    use ResolvesTenant;

	protected PropertyTranslationService $translator;

	public function __construct(PropertyTranslationService $translator)
	{
		$this->translator = $translator;
	}

	public function mostViewed(Request $request, string $tenantId)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$days = min(365, max(1, (int) $request->query('days', 30)));
		$limit = min(50, max(1, (int) $request->query('limit', 10)));

		$startDate = Carbon::today()->subDays($days)->toDateString();
		$endDate = Carbon::today()->toDateString();

		$rows = DB::table('pageview_analytics')
			->where('tenant_id', $tenant->username)
			->where('page_type', 'property')
			->whereBetween('date_bucket', [$startDate, $endDate])
			->select(
				'page_slug as slug',
				DB::raw('MIN(page_path) as path'),
				DB::raw('SUM(views_count) as views')
			)
			->groupBy('page_slug')
			->orderByDesc('views')
			->limit($limit)
			->get();

		$slugs = $rows->pluck('slug')->filter()->unique()->values()->toArray();
		if ($slugs === []) {
			return response()->json([
				'data' => [],
				'meta' => [
					'days' => $days,
					'limit' => $limit,
				],
			]);
		}

		$contentRows = DB::table('user_property_contents')
			->where('user_id', $tenant->id)
			->whereIn('slug', $slugs)
			->get(['property_id', 'slug']);

		$slugToPropertyId = $contentRows->keyBy('slug')->map->property_id;

		$propertyIds = $contentRows->pluck('property_id')->unique()->values();
		$propertiesById = Property::query()
			->with(['contents', 'galleryImages', 'project.contents'])
			->where('user_id', $tenant->id)
			->when(config('properties.backfill_complete'), function ($q) {
				$q->where('publish_status', 'published');
			}, function ($q) {
				$q->where('status', 1)->where(function ($inner) {
					$inner->where('publish_status', 'published')->orWhereNull('publish_status');
				});
			})
			->whereIn('id', $propertyIds)
			->get()
			->keyBy('id');

		$stateIds = $propertiesById->flatMap(fn ($p) => $p->contents->pluck('state_id'))->filter()->unique()->values();
		$districtsMap = UserDistrict::with('city')
			->whereIn('id', $stateIds)
			->get()
			->keyBy('id');

		$viewsMap = $rows->pluck('views', 'slug')->toArray();

		$items = $rows->map(function ($row) use ($slugToPropertyId, $propertiesById, $districtsMap, $viewsMap) {
			$slug = $row->slug;
			$propertyId = $slugToPropertyId[$slug] ?? null;
			if (! $propertyId || ! isset($propertiesById[$propertyId])) {
				return null;
			}
			$p = $propertiesById[$propertyId];
			$views = (int) ($viewsMap[$slug] ?? 0);

			return $this->mapPropertyToListItem($p, $views, $districtsMap);
		})->filter()->values();

		return response()->json([
			'data' => $items,
			'meta' => [
				'days' => $days,
				'limit' => $limit,
			],
		]);
	}

    public function index(Request $request, string $tenantId)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$query = Property::query()
			->with(['contents', 'galleryImages', 'project.contents'])
			->where('user_id', $tenant->id);

		if (config('properties.backfill_complete')) {
			$query->where('publish_status', 'published');
		} else {
			$query->where('status', 1)
				->where(function ($q) {
					$q->where('publish_status', 'published')->orWhereNull('publish_status');
				});
		}

		if ($unitStatus = $request->query('unit_status')) {
			$query->where('unit_status', $unitStatus);
		}

		// Filters
		if ($purpose = $request->query('purpose')) {
			$this->applyPurposeFilter($query, $purpose);
		} elseif ($transactionType = $request->query('transactionType_en')) {
			$this->applyPurposeFilter($query, $transactionType);
		}
		if ($q = $request->query('q')) {
			$query->whereHas('contents', function ($qbuilder) use ($q) {
				$qbuilder->where('title', 'like', "%{$q}%")
					->orWhere('address', 'like', "%{$q}%");
			});
		}
		foreach (['property_type','beds','bath','city_id','state_id','category_id','project_id'] as $eq) {
			if (!is_null($request->query($eq))) {
				if (in_array($eq, ['city_id','state_id','category_id'])) {
					$query->whereHas('contents', function ($qbuilder) use ($eq, $request) {
						$qbuilder->where($eq, $request->query($eq));
					});
				} else {
					$query->where($eq, $request->query($eq));
				}
			}
		}
		// Featured filter
		if ($request->boolean('featured')) {
			$query->where('featured', 1);
		}
		if ($request->filled('price_from')) $query->where('price', '>=', $request->query('price_from'));
		if ($request->filled('price_to')) $query->where('price', '<=', $request->query('price_to'));
		if ($request->filled('area_from')) $query->where('area', '>=', $request->query('area_from'));
		if ($request->filled('area_to')) $query->where('area', '<=', $request->query('area_to'));

		// Features (JSON array)
		if ($features = $request->query('features')) {
			$featuresArray = array_filter(array_map('trim', explode(',', $features)));
			foreach ($featuresArray as $feature) {
				$query->whereJsonContains('features', $feature);
			}
		}

		// Sort
		switch ($request->query('sort')) {
			case 'most_viewed':
				$days = min(365, max(1, (int) $request->query('days', 30)));
				$startDate = Carbon::today()->subDays($days)->toDateString();
				$endDate = Carbon::today()->toDateString();

				$pvSub = DB::table('pageview_analytics as pa')
					->join('user_property_contents as upc', 'upc.slug', '=', 'pa.page_slug')
					->where('pa.tenant_id', $tenant->username)
					->where('pa.page_type', 'property')
					->whereBetween('pa.date_bucket', [$startDate, $endDate])
					->where('upc.user_id', $tenant->id)
					->select('upc.property_id', DB::raw('SUM(pa.views_count) as pv_total'))
					->groupBy('upc.property_id');

				$query
					->leftJoinSub($pvSub, 'mv_pv', function ($join) {
						$join->on('mv_pv.property_id', '=', 'user_properties.id');
					})
					->orderByDesc(DB::raw('COALESCE(mv_pv.pv_total, 0)'))
					->orderBy('created_at', 'desc');
				break;
			case 'price_asc':
				$query->orderBy('price', 'asc');
				break;
			case 'price_desc':
				$query->orderBy('price', 'desc');
				break;
			case 'area_asc':
				$query->orderBy('area', 'asc');
				break;
			case 'area_desc':
				$query->orderBy('area', 'desc');
				break;
			case 'reorder_asc':
				$query->orderBy('reorder', 'asc');
				break;
			case 'reorder_desc':
				$query->orderBy('reorder', 'desc');
				break;
			case 'reorder_featured_asc':
				$query->orderBy('reorder_featured', 'asc');
				break;
			case 'reorder_featured_desc':
				$query->orderBy('reorder_featured', 'desc');
				break;
			case 'featured_first':
				$query->orderBy('featured', 'desc')->orderBy('reorder_featured', 'desc')->orderBy('reorder', 'asc')->orderBy('created_at', 'desc');
				break;
			default:
				$query->orderBy('featured', 'desc')->orderBy('reorder_featured', 'desc')->orderBy('reorder', 'asc')->orderBy('created_at', 'desc');
		}

		// Homepage helpers
		if ($request->boolean('featured')) {
			$query->where('featured', 1);
		}
		if ($request->boolean('latest')) {
			$query->orderBy('created_at', 'desc');
		}

        $perPage = min((int) $request->query('per_page', (int) $request->query('limit', 20)), 50);
        $properties = $query->paginate($perPage);

        // Pre-load districts and cities to avoid N+1 queries
        $stateIds = $properties->getCollection()
            ->flatMap(fn($p) => $p->contents->pluck('state_id'))
            ->filter()
            ->unique()
            ->values();

        $districtsMap = UserDistrict::with('city')
            ->whereIn('id', $stateIds)
            ->get()
            ->keyBy('id');

        $days = min(365, max(1, (int) $request->query('days', 30)));
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $slugs = $properties->getCollection()
            ->map(fn ($p) => $p->contents->first()?->slug)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $viewsBySlug = [];
        if ($slugs !== []) {
            $viewsBySlug = DB::table('pageview_analytics')
                ->where('tenant_id', $tenant->username)
                ->where('page_type', 'property')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->whereIn('page_slug', $slugs)
                ->select('page_slug', DB::raw('SUM(views_count) as total'))
                ->groupBy('page_slug')
                ->pluck('total', 'page_slug')
                ->toArray();
        }

		$items = $properties->getCollection()->map(function ($p) use ($viewsBySlug, $districtsMap) {
            $content = optional($p->contents->first());
            $slug    = $content?->slug;
            $views = (int) ($viewsBySlug[$slug] ?? 0);

            return $this->mapPropertyToListItem($p, $views, $districtsMap);
        });

        return response()->json([
            'properties' => $items,
            'pagination' => [
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'from' => $properties->firstItem(),
                'to' => $properties->lastItem(),
            ],
        ]);
	}

	public function show(Request $request, string $tenantId, string $slug)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$property = Property::with([
			'category',
			'user',
			'contents',
			'galleryImages',
			'proertyAmenities.amenity',
			'UserPropertyCharacteristics.UserFacade',
			'building',
			'project.contents',
		])
			->where('user_id', $tenant->id)
			->where('status', 1)
			->whereHas('contents', function ($q) use ($slug) {
				$q->where('slug', $slug);
			})
			->firstOrFail();

        $content = $property->contents->first();

        $districtsMap = collect();
        if ($content && $content->state_id) {
            $districtsMap = UserDistrict::with('city')
                ->whereIn('id', [$content->state_id])
                ->get()
                ->keyBy('id');
        }
        $district = $content && $content->state_id && isset($districtsMap[$content->state_id])
            ? $districtsMap[$content->state_id]
            : null;
        $city     = $district?->city;
        $districtStr = trim(implode(' - ', array_filter([$district?->name_ar ?? null, $city?->name_ar ?? null])));

        $featured = $property->featured_image ? asset($property->featured_image) : null;
        $gallery  = $property->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
        $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        $days = min(365, max(1, (int) $request->query('days', 30)));
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();
        $views = 0;
        if ($content?->slug) {
            $views = (int) DB::table('pageview_analytics')
                ->where('tenant_id', $tenant->username)
                ->where('page_type', 'property')
                ->where('page_slug', $content->slug)
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->sum('views_count');
        }

        $normalizedPurpose = match ($property->purpose) {
            'rented' => 'rent',
            'sold' => 'sale',
            default => $property->purpose,
        };
        $isUnavailable = in_array($property->purpose, ['rented', 'sold'], true);

		$data = [
            'id' => (string) $property->id,
            'slug' => $content?->slug ?? '',
            'title' => $content?->title ?? '',
            'district' => $districtStr,
            'price' => isset($property->price) ? formatNumberWithoutTrailingZeros($property->price) : '0',
            'views' => $views,
            'bedrooms' => (int) ($property->beds ?? 0),
            'bathrooms' => (int) ($property->bath ?? 0),
            'area' => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : '0',
            'property_type' => $this->translator->translateType($property->property_type),
            'property_type_en' => $property->property_type ?? '',
            'transactionType' => $this->translator->translatePurpose($normalizedPurpose),
            'transactionType_en' => $normalizedPurpose,
            'image' => $featured,
            'status' => $isUnavailable ? 'unavailable' : 'available',
            'createdAt' => $property->created_at?->toISOString(),
            'description' => $content?->description ?? '',
            'features' => is_string($property->features) ? [$property->features] : (is_array($property->features) ? $property->features : []),
            'location' => [
                'lat' => $property->latitude ? (float) $property->latitude : null,
                'lng' => $property->longitude ? (float) $property->longitude : null,
                'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
            ],
            'images' => $images,
        ];

		// Merge in extended fields to mirror admin show response
		$characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];

		// Add facade name if facade_id exists
		if (isset($characteristics['facade_id']) && $characteristics['facade_id'] && $property->UserPropertyCharacteristics) {
			$facade = $property->UserPropertyCharacteristics->UserFacade;
			if ($facade) {
				$characteristics['facade_name'] = $facade->name;
			}
		}

		// Remove characteristic's id to prevent overwriting property id
		unset($characteristics['id']);

		// Get project data if relationship is loaded
		$projectData = null;
		if ($property->relationLoaded('project') && $property->project) {
			$projectContent = $property->project->contents->first();
			$projectData = [
				'id' => $property->project->id,
				'title' => optional($projectContent)->title ?? '',
				'slug' => optional($projectContent)->slug ?? '',
				'featured_image' => $property->project->featured_image ? asset($property->project->featured_image) : null,
			];
		}

		$extra = [
			'payment_method' => $this->translator->translatePaymentMethod($property->payment_method),
			'payment_method_en' => $property->payment_method,
			'pricePerMeter' => isset($property->pricePerMeter) ? formatNumberWithoutTrailingZeros($property->pricePerMeter) : null,
			'floor_planning_image' => collect($property->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
			'video_url' => $property->video_url ? asset($property->video_url) : null,
			'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
			'video_image' => $property->video_image ? asset($property->video_image) : null,
			'faqs' => $property->faqs ?? [],
			'building' => $property->building,
			'project' => $projectData,
		];

		$data = array_merge($data, $extra, $characteristics);

		return response()->json(['property' => $data]);
	}

	/**
	 * Apply purpose / transaction type filter (rent/rented, sale/sold).
	 */
	protected function applyPurposeFilter(Builder $query, string $purpose): void
	{
		$purposeMap = [
			'rent' => ['rent', 'rented'],
			'sale' => ['sale', 'sold'],
		];
		if (isset($purposeMap[$purpose])) {
			$query->whereIn('purpose', $purposeMap[$purpose]);
		} else {
			$query->where('purpose', $purpose);
		}
	}

	/**
	 * Map a property to the public tenant website list item shape (index + mostViewed).
	 *
	 * @param  \Illuminate\Support\Collection<int, \App\Models\User\UserDistrict>  $districtsMap
	 */
	protected function mapPropertyToListItem(Property $p, int $views, $districtsMap): array
	{
		$content = optional($p->contents->first());
		$slug = $content?->slug;

		$district = $content && $content->state_id && isset($districtsMap[$content->state_id])
			? $districtsMap[$content->state_id]
			: null;
		$city = $district?->city;
		$districtStr = trim(implode(' - ', array_filter([$district?->name_ar ?? null, $city?->name_ar ?? null])));

		$featured = $p->featured_image ? asset($p->featured_image) : null;
		$gallery = $p->galleryImages->pluck('image')->map(fn ($img) => asset($img))->toArray();
		$images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

		$normalizedPurpose = match ($p->purpose) {
			'rented' => 'rent',
			'sold' => 'sale',
			default => $p->purpose,
		};
		$isUnavailable = in_array($p->purpose, ['rented', 'sold'], true);

		$projectData = null;
		if ($p->relationLoaded('project') && $p->project) {
			$projectContent = $p->project->contents->first();
			$projectData = [
				'id' => $p->project->id,
				'title' => optional($projectContent)->title ?? '',
				'slug' => optional($projectContent)->slug ?? '',
			];
		}

		return [
			'id' => (string) $p->id,
			'slug' => $slug,
			'title' => $content?->title ?? '',
			'district' => $districtStr,
			'price' => isset($p->price) ? formatNumberWithoutTrailingZeros($p->price) : '0',
			'views' => $views,
			'bedrooms' => (int) ($p->beds ?? 0),
			'bathrooms' => (int) ($p->bath ?? 0),
			'area' => isset($p->area) ? formatNumberWithoutTrailingZeros($p->area) : '0',
			'property_type' => $this->translator->translateType($p->property_type),
			'property_type_en' => $p->property_type,
			'transactionType' => $this->translator->translatePurpose($normalizedPurpose),
			'transactionType_en' => $normalizedPurpose,
			'image' => $featured,
			'featured' => (bool) $p->featured,
			'unit_status' => $p->unit_status ?? ($isUnavailable ? 'sold' : 'available'),
			'listing_purpose' => $p->listing_purpose,
			'publish_status' => $p->publish_status,
			'status' => $isUnavailable ? 'unavailable' : 'available',
			'show_reservations' => (bool) $p->show_reservations,
			'createdAt' => $p->created_at?->toISOString(),
			'description' => $content?->description ?? '',
			'features' => is_array($p->features) ? $p->features : [],
			'location' => [
				'lat' => $p->latitude ? (float) $p->latitude : null,
				'lng' => $p->longitude ? (float) $p->longitude : null,
				'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
			],
			'images' => $images,
			'project' => $projectData,
		];
	}
}
