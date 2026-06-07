<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Http\Resources\Api\V1\TenantWebsite\PropertyPublicResource;
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
			->publishedForPublic()
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

			return PropertyPublicResource::toListArray($p, $views, $districtsMap, $this->translator);
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

		$query->publishedForPublic();

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

            return PropertyPublicResource::toListArray($p, $views, $districtsMap, $this->translator);
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
			'contents',
			'galleryImages',
			'proertyAmenities.amenity',
			'UserPropertyCharacteristics.UserFacade',
			'building',
			'project.contents',
		])
			->where('user_id', $tenant->id)
			->publishedForPublic()
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

		return response()->json([
			'property' => PropertyPublicResource::toDetailArray($property, $views, $districtsMap, $this->translator),
		]);
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

}
