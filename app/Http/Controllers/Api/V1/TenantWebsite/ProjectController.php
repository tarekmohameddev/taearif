<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;

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

		$this->applyProjectStatusFilter($query, $request);
		$this->applyUnitCategoryFilter($query, $request);
		$this->applyPropertyTypeFilter($query, $request);
		$this->applyPublicUnitsRangeFilter($query, $request);

		// Sort by created_at DESC
		$query->orderBy('created_at', 'desc');

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
		if ($request->filled('published')) {
			$projectQuery->where('published', $request->boolean('published') ? 1 : 0);
		}
		if ($request->boolean('featured')) {
			$projectQuery->where('featured', 1);
		}

		$this->applyUnitCategoryFilter($projectQuery, $request);
		$this->applyPropertyTypeFilter($projectQuery, $request);
		$this->applyPublicUnitsRangeFilter($projectQuery, $request);

		$projectsTotal = (clone $projectQuery)->count();
		if ($projectsTotal === 0) {
			return response()->json([
				'filters' => [
					'project_statuses' => [
						['value' => 'finished', 'label' => 'Finished', 'count' => 0],
						['value' => 'not_finished', 'label' => 'Not Finished', 'count' => 0],
					],
					'unit_categories' => [],
					'property_types' => [],
					'units_range' => ['min' => 0, 'max' => 0],
					'projects_total' => 0,
				],
			]);
		}

		$projectIds = (clone $projectQuery)
			->pluck('id')
			->map(fn ($id) => (int) $id)
			->values()
			->all();

		$finishedCount = (clone $projectQuery)
			->where('complete_status', 1)
			->count();
		$notFinishedCount = (clone $projectQuery)
			->where(function (Builder $q) {
				$q->whereNull('complete_status')->orWhere('complete_status', '!=', 1);
			})
			->count();

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
				'project_statuses' => [
					['value' => 'finished', 'label' => 'Finished', 'count' => $finishedCount],
					['value' => 'not_finished', 'label' => 'Not Finished', 'count' => $notFinishedCount],
				],
				'unit_categories' => $unitCategories,
				'property_types' => $propertyTypes,
				'units_range' => ['min' => $minUnits, 'max' => $maxUnits],
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

	private function applyProjectStatusFilter(Builder $query, Request $request): void
	{
		$projectStatus = strtolower(trim((string) $request->query('project_status', '')));

		if ($projectStatus === 'finished') {
			$query->where('complete_status', 1);
			return;
		}

		if (in_array($projectStatus, ['not_finished', 'not-finished', 'unfinished'], true)) {
			$query->where(function (Builder $q) {
				$q->whereNull('complete_status')->orWhere('complete_status', '!=', 1);
			});
			return;
		}

		// Backward compatibility: existing numeric status filter.
		if ($request->filled('status')) {
			$status = (int) $request->query('status');
			if (in_array($status, [0, 1, 2], true)) {
				$query->where('complete_status', $status);
			}
		}
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

