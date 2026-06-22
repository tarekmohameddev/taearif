<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use Carbon\Carbon;

class ProjectController extends Controller
{
    use ResolvesTenant;

	/**
	 * Get amenities array from project, ensuring it's always an array.
	 * Uses the Attribute accessor which properly handles JSON decoding.
	 */
	private function getAmenitiesArray($project): array
	{
		return $project->amenities ?? [];
	}

	private function resolveMediaUrl(?string $value): ?string
	{
		if (empty($value)) {
			return null;
		}

		return str_starts_with($value, 'http://') || str_starts_with($value, 'https://')
			? $value
			: asset($value);
	}

    public function index(Request $request, string $tenantId)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$query = Project::query()
			->with([
				'contents',
				'galleryImages',
				'user',
				'properties' => fn ($propertyQuery) => $propertyQuery
					->publishedForPublic()
					->with(['contents', 'galleryImages']),
			])
			->withCount([
				'properties as public_units_count' => fn ($propertyQuery) => $propertyQuery->publishedForPublic(),
			])
			->where('user_id', $tenant->id);

		// Published filter (optional)
		if ($request->filled('published')) {
			$query->where('published', $request->boolean('published') ? 1 : 0);
		}

		// Featured filter
		if ($request->boolean('featured')) {
			$query->where('featured', 1);
		}

		$this->applyBrowseFilters($query, $request);
		$this->applyProjectSort($query, $request, $tenant);

		// Pagination with limit
        $perPage = min((int) $request->query('limit', 20), 50);
        $projects = $query->paginate($perPage);
		$projectIds = collect($projects->items())
			->pluck('id')
			->map(fn ($id) => (int) $id)
			->values()
			->all();
		$projectUnitBreakdowns = $this->getProjectUnitBreakdowns($projectIds);

        // Collect all slugs for GA4 query
        $slugs = collect($projects->items())
            ->map(fn($p) => optional($p->contents->first())?->slug)
            ->filter()
            ->values()
            ->all();

        // Fetch views from pageview_analytics table (synced from GA4)
        // OPTIMIZED: Query from local database instead of GA4 API for better performance
        $viewsBySlug = [];
        if (!empty($slugs)) {
            try {
                $days = (int) $request->query('days', 30);
                $startDate = \Carbon\Carbon::today()->subDays($days)->toDateString();
                $endDate = \Carbon\Carbon::today()->toDateString();
                $paths = [];
                foreach ($slugs as $slug) {
                    $paths[] = "/project/{$slug}";
                    $paths[] = "/ar/project/{$slug}";
                    $paths[] = "/en/project/{$slug}";
                }

                // Query from pageview_analytics table
                $viewsData = \Illuminate\Support\Facades\DB::table('pageview_analytics')
                    ->where('tenant_id', $tenant->username)
                    ->where('page_type', 'project')
                    ->whereBetween('date_bucket', [$startDate, $endDate])
                    ->whereIn('page_path', $paths)
                    ->select('page_path', \Illuminate\Support\Facades\DB::raw('SUM(views_count) as total_views'))
                    ->groupBy('page_path')
                    ->get();

                // Map views back to slugs
                foreach ($viewsData as $data) {
                    $path = $data->page_path;
                    $views = (int) $data->total_views;
                    foreach ($slugs as $slug) {
                        if (strpos($path, $slug) !== false) {
                            $viewsBySlug[$slug] = ($viewsBySlug[$slug] ?? 0) + $views;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error fetching project views from pageview_analytics', [
                    'tenant' => $tenant->username,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $items = collect($projects->items())->map(function ($project) use ($viewsBySlug, $projectUnitBreakdowns) {
            $content = optional($project->contents->first());
            $slug    = $content?->slug;

            // Images (full urls)
            $featured = $project->featured_image ? asset($project->featured_image) : null;
            $gallery  = $project->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
            $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

            return [
                'id' => (string) $project->id,
                'slug' => $slug,
                'title' => $content?->title ?? '',
                'description' => $content?->description ?? '',
                'address' => $content?->address ?? '',
                'developer' => $project->developer ?? '',
                'units' => (int) ($project->units ?? 0),
                'unitsCount' => (int) ($projectUnitBreakdowns[$project->id]['total'] ?? $project->public_units_count ?? 0),
                'unitBreakdown' => $projectUnitBreakdowns[$project->id]['by_category'] ?? [],
                'completionDate' => $project->completion_date ?? '',
                'completeStatus' => $project->complete_status ?? '',
                'minPrice' => isset($project->min_price) ? (string) $project->min_price : '0',
                'maxPrice' => isset($project->max_price) ? (string) $project->max_price : '0',
                'image' => $featured,
                'featured' => (bool) $project->featured,
                'images' => $images,
                'videoUrl' => $project->video_url ?? null,
                'brochure' => $this->resolveMediaUrl($project->brochure),
                'amenities' => $this->getAmenitiesArray($project),
                'views' => $viewsBySlug[$slug] ?? 0,
                'location' => [
                    'lat' => $project->latitude ? (float) $project->latitude : null,
                    'lng' => $project->longitude ? (float) $project->longitude : null,
                    'address' => $content?->address ?? '',
                ],
                'properties' => $project->properties->map(function ($property) {
                    return $this->formatProperty($property);
                }),
            ];
        });

        return response()->json([
            'projects' => $items,
            'pagination' => [
                'total' => $projects->total(),
                'per_page' => $projects->perPage(),
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
            ],
        ]);
	}

	public function filterOptions(Request $request, string $tenantId)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$projectQuery = Project::query()
			->where('user_id', $tenant->id)
			->withCount([
				'properties as public_units_count' => fn ($propertyQuery) => $propertyQuery->publishedForPublic(),
			]);

		// Keep the options dynamic for the same subset FE is browsing.
		$this->applyBrowseFilters($projectQuery, $request);

		$projectsTotal = (clone $projectQuery)->count();
		if ($projectsTotal === 0) {
			return response()->json([
				'filters' => $this->emptyFilterOptionsResponse(),
			]);
		}

		$projectIds = (clone $projectQuery)
			->pluck('id')
			->map(fn ($id) => (int) $id)
			->values()
			->all();

		$completeStatuses = $this->buildCompleteStatusOptions(
			$this->rebuildBrowseQuery($tenant->id, $request, ['complete_status'])
		);

		$listingPurposes = $this->buildListingPurposeOptions(
			$this->rebuildBrowseQuery($tenant->id, $request, ['listing_purpose'])
		);

		$unitStatuses = $this->buildUnitStatusOptions(
			$this->rebuildBrowseQuery($tenant->id, $request, ['unit_status'])
		);

		$priceRange = $this->buildPriceRange(
			$this->rebuildBrowseQuery($tenant->id, $request, ['price'])
		);

		$basePropertyQuery = Property::query()
			->publishedForPublic()
			->where('user_id', $tenant->id)
			->whereIn('project_id', $projectIds);

		$unitCountsByProject = (clone $basePropertyQuery)
			->selectRaw('project_id, COUNT(*) as total')
			->groupBy('project_id')
			->pluck('total', 'project_id')
			->map(fn ($count) => (int) $count)
			->values();

		$minUnits = $unitCountsByProject->isEmpty() ? 0 : (int) $unitCountsByProject->min();
		$maxUnits = $unitCountsByProject->isEmpty() ? 0 : (int) $unitCountsByProject->max();

		$unitCategories = (clone $basePropertyQuery)
			->join('api_user_categories', 'api_user_categories.id', '=', 'user_properties.category_id')
			->selectRaw('
				api_user_categories.id,
				api_user_categories.slug,
				api_user_categories.name,
				COUNT(*) as units_count,
				COUNT(DISTINCT user_properties.project_id) as projects_count
			')
			->groupBy('api_user_categories.id', 'api_user_categories.slug', 'api_user_categories.name')
			->orderByDesc('units_count')
			->get()
			->map(function ($row) {
				return [
					'id' => (int) $row->id,
					'slug' => (string) $row->slug,
					'name' => (string) $row->name,
					'units_count' => (int) $row->units_count,
					'projects_count' => (int) $row->projects_count,
				];
			})
			->values();

		$propertyTypes = (clone $basePropertyQuery)
			->whereNotNull('user_properties.property_type')
			->where('user_properties.property_type', '!=', '')
			->selectRaw('
				user_properties.property_type as value,
				COUNT(*) as units_count,
				COUNT(DISTINCT user_properties.project_id) as projects_count
			')
			->groupBy('user_properties.property_type')
			->orderBy('user_properties.property_type')
			->get()
			->map(function ($row) {
				return [
					'value' => (string) $row->value,
					'units_count' => (int) $row->units_count,
					'projects_count' => (int) $row->projects_count,
				];
			})
			->values();

		return response()->json([
			'filters' => [
				'complete_statuses' => $completeStatuses,
				'listing_purposes' => $listingPurposes,
				'unit_statuses' => $unitStatuses,
				'unit_categories' => $unitCategories,
				'property_types' => $propertyTypes,
				'units_range' => ['min' => $minUnits, 'max' => $maxUnits],
				'price_range' => $priceRange,
				'projects_total' => $projectsTotal,
			],
		]);
	}

	public function show(Request $request, string $tenantId, string $slug)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		// Validate slug format (allow Unicode/Arabic characters)
		// Block only dangerous characters: null bytes, control chars
		if (preg_match('/[\x00-\x1F\x7F]/', $slug) || empty(trim($slug))) {
			return response()->json([
				'error' => 'Invalid slug format'
			], 400);
		}

		$project = Project::with([
			'contents',
			'galleryImages',
			'floorplanImages',
			'specifications',
			'types',
			'properties' => fn ($q) => $q->publishedForPublic(),
			'properties.contents',
			'properties.galleryImages',
		])
			->where('user_id', $tenant->id)
			->whereHas('contents', function ($q) use ($slug) {
				$q->where('slug', $slug);
			})
			->firstOrFail();

		// Get the content matching the slug (in case of multi-language)
		$content = $project->contents->where('slug', $slug)->first();

		// Fallback to first content if slug match not found
		if (!$content) {
			$content = $project->contents->first();
		}

		// If still no content, return error
		if (!$content) {
			return response()->json([
				'error' => 'Project content not found'
			], 404);
		}

		// Images (full urls)
		$featured = $project->featured_image ? asset($project->featured_image) : null;
		$gallery  = $project->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
		$images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

		// Floorplan images (full urls)
		$floorplans = $project->floorplanImages->pluck('image')->map(fn($img) => asset($img))->toArray();

		// Specifications
		$specifications = $project->specifications->map(function ($spec) {
			return [
				'key' => $spec->key ?? '',
				'label' => $spec->label ?? '',
				'value' => $spec->value ?? '',
			];
		})->toArray();

		// Types
		$types = $project->types->map(function ($type) {
			return [
				'title' => $type->title ?? '',
				'minArea' => isset($type->min_area) ? (string) $type->min_area : '0',
				'maxArea' => isset($type->max_area) ? (string) $type->max_area : '0',
				'minPrice' => isset($type->min_price) ? (string) $type->min_price : '0',
				'maxPrice' => isset($type->max_price) ? (string) $type->max_price : '0',
				'unit' => $type->unit ?? '',
			];
		})->toArray();

		// Fetch views from pageview_analytics table (synced from GA4)
		// OPTIMIZED: Query from local database instead of GA4 API for better performance
		$views = 0;
		try {
			$days = (int) $request->query('days', 30);
			$startDate = \Carbon\Carbon::today()->subDays($days)->toDateString();
			$endDate = \Carbon\Carbon::today()->toDateString();
			$paths = [
				"/project/{$slug}",
				"/ar/project/{$slug}",
				"/en/project/{$slug}",
			];

			// Query from pageview_analytics table
			$viewsData = \Illuminate\Support\Facades\DB::table('pageview_analytics')
				->where('tenant_id', $tenant->username)
				->where('page_type', 'project')
				->whereBetween('date_bucket', [$startDate, $endDate])
				->whereIn('page_path', $paths)
				->select('page_path', \Illuminate\Support\Facades\DB::raw('SUM(views_count) as total_views'))
				->groupBy('page_path')
				->get();

			// Sum views across all path variants
			foreach ($viewsData as $data) {
				$views += (int) $data->total_views;
			}
		} catch (\Exception $e) {
			\Log::error('Error fetching project views from pageview_analytics', [
				'tenant' => $tenant->username,
				'slug' => $slug,
				'error' => $e->getMessage(),
			]);
		}

		$data = [
			'id' => (string) $project->id,
			'slug' => $content->slug ?? '',
			'title' => $content->title ?? '',
			'description' => $content->description ?? '',
			'address' => $content->address ?? '',
			'metaKeyword' => $content->meta_keyword ?? '',
			'metaDescription' => $content->meta_description ?? '',
			'developer' => $project->developer ?? '',
			'units' => (int) ($project->units ?? 0),
			'completionDate' => $project->completion_date ?? '',
			'completeStatus' => $project->complete_status ?? '',
			'minPrice' => isset($project->min_price) ? (string) $project->min_price : '0',
			'maxPrice' => isset($project->max_price) ? (string) $project->max_price : '0',
			'image' => $featured,
			'images' => $images,
			'floorplans' => $floorplans,
			'videoUrl' => $project->video_url ?? null,
			'brochure' => $this->resolveMediaUrl($project->brochure),
			'views' => $views,
			'amenities' => $this->getAmenitiesArray($project),
			'featured' => (bool) ($project->featured ?? false),
			'published' => (bool) ($project->published ?? false),
			'location' => [
				'lat' => $project->latitude ? (float) $project->latitude : null,
				'lng' => $project->longitude ? (float) $project->longitude : null,
				'address' => $content->address ?? '',
			],
			'specifications' => $specifications,
			'types' => $types,
			'createdAt' => $project->created_at?->toISOString(),
			'updatedAt' => $project->updated_at?->toISOString(),
			'properties' => $project->properties->map(function ($property) {
				return $this->formatProperty($property);
			}),
		];

		return response()->json(['project' => $data]);
	}

	/**
	 * Format a property with all its details for API response.
	 */
	private function formatProperty($property): array
	{
		$content = $property->contents->first();

		return [
			'id' => $property->id,
			'project_id' => $property->project_id,
			'title' => optional($content)->title ?? '',
			'slug' => optional($content)->slug ?? '',
			'address' => optional($content)->address ?? '',
			'description' => optional($content)->description ?? '',
			'price' => $property->price,
			'pricePerMeter' => $property->pricePerMeter,
			'purpose' => $property->purpose,
			'property_type' => $property->property_type,
			'beds' => $property->beds,
			'bath' => $property->bath,
			'area' => $property->area,
			'size' => $property->size,
			'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
			'gallery' => $property->galleryImages->map(fn($img) => asset($img->image))->toArray(),
			'location' => [
				'latitude' => $property->latitude,
				'longitude' => $property->longitude,
			],
			'unit_status' => $property->unit_status ?? 'available',
			'listing_purpose' => $property->listing_purpose,
			'publish_status' => $property->publish_status,
			'status' => (bool) $property->status,
			'featured' => (bool) $property->featured,
			'show_reservations' => (bool) $property->show_reservations,
			'property_status' => $property->property_status,
			'features' => $property->features ?? [],
			'faqs' => $property->faqs ?? [],
			'category_id' => $property->category_id,
			'payment_method' => $property->payment_method,
			'video_url' => $property->video_url ? asset($property->video_url) : null,
			'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
			'created_at' => $property->created_at?->toISOString(),
			'updated_at' => $property->updated_at?->toISOString(),
		];
	}

	private const COMPLETE_STATUS_LABELS = [
		0 => 'In progress',
		1 => 'Finished',
		2 => 'Not started',
	];

	private const LISTING_PURPOSE_LABELS = [
		'sale' => 'Sale',
		'rent' => 'Rent',
	];

	private const UNIT_STATUS_LABELS = [
		'available' => 'Available',
		'reserved' => 'Reserved',
		'sold' => 'Sold',
		'rented' => 'Rented',
	];

	/**
	 * @param  list<string>  $except
	 */
	private function applyBrowseFilters(Builder $query, Request $request, array $except = []): void
	{
		if (! in_array('published', $except, true) && $request->filled('published')) {
			$query->where('published', $request->boolean('published') ? 1 : 0);
		}

		if (! in_array('featured', $except, true) && $request->boolean('featured')) {
			$query->where('featured', 1);
		}

		if (! in_array('complete_status', $except, true)) {
			$this->applyCompleteStatusFilter($query, $request);
		}

		if (! in_array('unit_category', $except, true)) {
			$this->applyUnitCategoryFilter($query, $request);
		}

		if (! in_array('property_type', $except, true)) {
			$this->applyPropertyTypeFilter($query, $request);
		}

		if (! in_array('units_range', $except, true)) {
			$this->applyPublicUnitsRangeFilter($query, $request);
		}

		if (! in_array('price', $except, true)) {
			$this->applyPriceRangeFilter($query, $request);
		}

		if (! in_array('listing_purpose', $except, true)) {
			$this->applyListingPurposeFilter($query, $request);
		}

		if (! in_array('unit_status', $except, true)) {
			$this->applyUnitStatusFilter($query, $request);
		}

		if (! in_array('search', $except, true)) {
			$this->applyProjectSearchFilter($query, $request);
		}
	}

	/**
	 * @param  list<string>  $except
	 */
	private function rebuildBrowseQuery(int $tenantUserId, Request $request, array $except): Builder
	{
		$query = Project::query()->where('user_id', $tenantUserId);
		$this->applyBrowseFilters($query, $request, $except);

		return $query;
	}

	/**
	 * @return array{complete_statuses: list<array{value: int, label: string, count: int}>, listing_purposes: list<array{value: string, label: string, projects_count: int, units_count: int}>, unit_statuses: list<array{value: string, label: string, projects_count: int, units_count: int}>, unit_categories: list<mixed>, property_types: list<mixed>, units_range: array{min: int, max: int}, price_range: array{min: int, max: int}, projects_total: int}
	 */
	private function emptyFilterOptionsResponse(): array
	{
		return [
			'complete_statuses' => $this->emptyCompleteStatusOptions(),
			'listing_purposes' => $this->emptyListingPurposeOptions(),
			'unit_statuses' => $this->emptyUnitStatusOptions(),
			'unit_categories' => [],
			'property_types' => [],
			'units_range' => ['min' => 0, 'max' => 0],
			'price_range' => ['min' => 0, 'max' => 0],
			'projects_total' => 0,
		];
	}

	private function applyProjectSort(Builder $query, Request $request, $tenant): void
	{
		switch ($request->query('sort')) {
			case 'price_asc':
				$query->orderBy('min_price', 'asc')->orderBy('created_at', 'desc');
				break;
			case 'price_desc':
				$query->orderBy('max_price', 'desc')->orderBy('created_at', 'desc');
				break;
			case 'completion_date_asc':
				$query->orderBy('completion_date', 'asc')->orderBy('created_at', 'desc');
				break;
			case 'completion_date_desc':
				$query->orderBy('completion_date', 'desc')->orderBy('created_at', 'desc');
				break;
			case 'most_viewed':
				$days = min(365, max(1, (int) $request->query('days', 30)));
				$startDate = Carbon::today()->subDays($days)->toDateString();
				$endDate = Carbon::today()->toDateString();

				$pvSub = DB::table('pageview_analytics as pa')
					->join('user_project_contents as upc', 'upc.slug', '=', 'pa.page_slug')
					->where('pa.tenant_id', $tenant->username)
					->where('pa.page_type', 'project')
					->whereBetween('pa.date_bucket', [$startDate, $endDate])
					->where('upc.user_id', $tenant->id)
					->select('upc.project_id', DB::raw('SUM(pa.views_count) as pv_total'))
					->groupBy('upc.project_id');

				$query
					->select('user_projects.*')
					->leftJoinSub($pvSub, 'mv_pv', function ($join) {
						$join->on('mv_pv.project_id', '=', 'user_projects.id');
					})
					->orderByDesc(DB::raw('COALESCE(mv_pv.pv_total, 0)'))
					->orderBy('user_projects.created_at', 'desc');
				break;
			case 'newest':
			default:
				$query->orderBy('created_at', 'desc');
				break;
		}
	}

	private function applyPriceRangeFilter(Builder $query, Request $request): void
	{
		$priceFrom = $request->query('price_from');
		if ($priceFrom === null || $priceFrom === '') {
			$priceFrom = $request->query('min_price');
		}

		$priceTo = $request->query('price_to');
		if ($priceTo === null || $priceTo === '') {
			$priceTo = $request->query('max_price');
		}

		if (is_numeric($priceFrom)) {
			$from = (float) $priceFrom;
			$query->where(function (Builder $priceQuery) use ($from) {
				$priceQuery
					->where('max_price', '>=', $from)
					->orWhere(function (Builder $fallbackQuery) use ($from) {
						$fallbackQuery
							->whereNull('max_price')
							->where('min_price', '>=', $from);
					});
			});
		}

		if (is_numeric($priceTo)) {
			$query->where('min_price', '<=', (float) $priceTo);
		}
	}

	private function applyListingPurposeFilter(Builder $query, Request $request): void
	{
		$listingPurpose = strtolower(trim((string) $request->query('listing_purpose', '')));
		if ($listingPurpose === '' || ! array_key_exists($listingPurpose, self::LISTING_PURPOSE_LABELS)) {
			return;
		}

		$query->whereHas('properties', function (Builder $propertyQuery) use ($listingPurpose) {
			$propertyQuery->publishedForPublic()->where('listing_purpose', $listingPurpose);
		});
	}

	private function applyUnitStatusFilter(Builder $query, Request $request): void
	{
		$unitStatus = strtolower(trim((string) $request->query('unit_status', '')));
		if ($unitStatus === '' || ! array_key_exists($unitStatus, self::UNIT_STATUS_LABELS)) {
			return;
		}

		$query->whereHas('properties', function (Builder $propertyQuery) use ($unitStatus) {
			$propertyQuery->publishedForPublic()->where('unit_status', $unitStatus);
		});
	}

	private function applyProjectSearchFilter(Builder $query, Request $request): void
	{
		$search = trim((string) $request->query('q', ''));
		if ($search === '') {
			return;
		}

		$query->where(function (Builder $searchQuery) use ($search) {
			$searchQuery
				->whereHas('contents', function (Builder $contentQuery) use ($search) {
					$contentQuery
						->where('title', 'like', "%{$search}%")
						->orWhere('address', 'like', "%{$search}%");
				})
				->orWhere('developer', 'like', "%{$search}%");
		});
	}

	/**
	 * @return array{min: int, max: int}
	 */
	private function buildPriceRange(Builder $projectQuery): array
	{
		$row = (clone $projectQuery)
			->selectRaw('MIN(min_price) as min_price, MAX(max_price) as max_price')
			->first();

		return [
			'min' => (int) ($row->min_price ?? 0),
			'max' => (int) ($row->max_price ?? 0),
		];
	}

	/**
	 * @return list<array{value: string, label: string, projects_count: int, units_count: int}>
	 */
	private function buildListingPurposeOptions(Builder $projectQuery): array
	{
		$options = [];
		foreach (array_keys(self::LISTING_PURPOSE_LABELS) as $value) {
			$scopedQuery = (clone $projectQuery)->whereHas('properties', function (Builder $propertyQuery) use ($value) {
				$propertyQuery->publishedForPublic()->where('listing_purpose', $value);
			});

			$options[] = [
				'value' => $value,
				'label' => self::LISTING_PURPOSE_LABELS[$value],
				'projects_count' => (clone $scopedQuery)->count(),
				'units_count' => $this->countPublicUnitsForProjects($scopedQuery, ['listing_purpose' => $value]),
			];
		}

		return $options;
	}

	/**
	 * @return list<array{value: string, label: string, projects_count: int, units_count: int}>
	 */
	private function buildUnitStatusOptions(Builder $projectQuery): array
	{
		$options = [];
		foreach (array_keys(self::UNIT_STATUS_LABELS) as $value) {
			$scopedQuery = (clone $projectQuery)->whereHas('properties', function (Builder $propertyQuery) use ($value) {
				$propertyQuery->publishedForPublic()->where('unit_status', $value);
			});

			$options[] = [
				'value' => $value,
				'label' => self::UNIT_STATUS_LABELS[$value],
				'projects_count' => (clone $scopedQuery)->count(),
				'units_count' => $this->countPublicUnitsForProjects($scopedQuery, ['unit_status' => $value]),
			];
		}

		return $options;
	}

	/**
	 * @return list<array{value: string, label: string, projects_count: int, units_count: int}>
	 */
	private function emptyListingPurposeOptions(): array
	{
		$options = [];
		foreach (self::LISTING_PURPOSE_LABELS as $value => $label) {
			$options[] = [
				'value' => $value,
				'label' => $label,
				'projects_count' => 0,
				'units_count' => 0,
			];
		}

		return $options;
	}

	/**
	 * @return list<array{value: string, label: string, projects_count: int, units_count: int}>
	 */
	private function emptyUnitStatusOptions(): array
	{
		$options = [];
		foreach (self::UNIT_STATUS_LABELS as $value => $label) {
			$options[] = [
				'value' => $value,
				'label' => $label,
				'projects_count' => 0,
				'units_count' => 0,
			];
		}

		return $options;
	}

	/**
	 * @param  array<string, string>  $propertyConstraints
	 */
	private function countPublicUnitsForProjects(Builder $projectQuery, array $propertyConstraints): int
	{
		$projectIds = (clone $projectQuery)->pluck('id')->map(fn ($id) => (int) $id)->all();
		if ($projectIds === []) {
			return 0;
		}

		$query = Property::query()
			->publishedForPublic()
			->whereIn('project_id', $projectIds);

		foreach ($propertyConstraints as $column => $value) {
			$query->where($column, $value);
		}

		return (int) $query->count();
	}

	private function applyCompleteStatusFilter(Builder $query, Request $request): void
	{
		$status = $request->query('status');
		if ($status === null || $status === '') {
			$status = $request->query('completeStatus');
		}

		if ($status === null || $status === '') {
			return;
		}

		$status = (int) $status;
		if (in_array($status, [0, 1, 2], true)) {
			$query->where('complete_status', $status);
		}
	}

	/**
	 * @return list<array{value: int, label: string, count: int}>
	 */
	private function buildCompleteStatusOptions(Builder $projectQuery): array
	{
		$options = [];
		foreach (array_keys(self::COMPLETE_STATUS_LABELS) as $value) {
			$options[] = [
				'value' => $value,
				'label' => self::COMPLETE_STATUS_LABELS[$value],
				'count' => (clone $projectQuery)->where('complete_status', $value)->count(),
			];
		}

		return $options;
	}

	/**
	 * @return list<array{value: int, label: string, count: int}>
	 */
	private function emptyCompleteStatusOptions(): array
	{
		$options = [];
		foreach (self::COMPLETE_STATUS_LABELS as $value => $label) {
			$options[] = [
				'value' => $value,
				'label' => $label,
				'count' => 0,
			];
		}

		return $options;
	}

	private function applyUnitCategoryFilter(Builder $query, Request $request): void
	{
		$categoryId = $request->query('unit_category_id');
		$categorySlug = strtolower(trim((string) ($request->query('unit_category') ?? $request->query('unit_category_slug') ?? '')));

		if ($categoryId !== null && $categoryId !== '') {
			$query->whereHas('properties', function (Builder $propertyQuery) use ($categoryId) {
				$propertyQuery->publishedForPublic()->where('category_id', (int) $categoryId);
			});
			return;
		}

		if ($categorySlug !== '') {
			$query->whereHas('properties', function (Builder $propertyQuery) use ($categorySlug) {
				$propertyQuery->publishedForPublic()
					->where(function (Builder $propertyTypeOrCategoryQuery) use ($categorySlug) {
						$propertyTypeOrCategoryQuery
							->where('property_type', $categorySlug)
							->orWhereHas('category', function (Builder $categoryQuery) use ($categorySlug) {
								$categoryQuery->where('slug', $categorySlug);
							});
					});
			});
		}
	}

	private function applyPropertyTypeFilter(Builder $query, Request $request): void
	{
		$propertyType = trim((string) $request->query('property_type', ''));
		if ($propertyType === '') {
			return;
		}

		$query->whereHas('properties', function (Builder $propertyQuery) use ($propertyType) {
			$propertyQuery->publishedForPublic()->where('property_type', $propertyType);
		});
	}

	private function applyPublicUnitsRangeFilter(Builder $query, Request $request): void
	{
		$minUnits = $request->query('min_units');
		if (is_numeric($minUnits)) {
			$query->whereHas(
				'properties',
				fn (Builder $propertyQuery) => $propertyQuery->publishedForPublic(),
				'>=',
				max(0, (int) $minUnits)
			);
		}

		$maxUnits = $request->query('max_units');
		if (is_numeric($maxUnits)) {
			$query->whereHas(
				'properties',
				fn (Builder $propertyQuery) => $propertyQuery->publishedForPublic(),
				'<=',
				max(0, (int) $maxUnits)
			);
		}
	}

	/**
	 * @param  array<int, int>  $projectIds
	 * @return array<int, array{total:int, by_category: array<string, int>}>
	 */
	private function getProjectUnitBreakdowns(array $projectIds): array
	{
		if ($projectIds === []) {
			return [];
		}

		$properties = Property::query()
			->publishedForPublic()
			->whereIn('project_id', $projectIds)
			->with('category:id,slug')
			->get(['id', 'project_id', 'category_id', 'property_type']);

		$breakdowns = [];
		foreach ($properties as $property) {
			$projectId = (int) $property->project_id;
			$categoryKey = $property->category?->slug
				?: (filled($property->property_type) ? $property->property_type : 'other');

			if (! isset($breakdowns[$projectId])) {
				$breakdowns[$projectId] = [
					'total' => 0,
					'by_category' => [],
				];
			}

			$breakdowns[$projectId]['total']++;
			$breakdowns[$projectId]['by_category'][$categoryKey] =
				($breakdowns[$projectId]['by_category'][$categoryKey] ?? 0) + 1;
		}

		return $breakdowns;
	}
}

