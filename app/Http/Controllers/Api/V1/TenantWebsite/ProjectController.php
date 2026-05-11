<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\Project;
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
			->with(['contents', 'galleryImages', 'user', 'properties.contents', 'properties.galleryImages'])
			->where('user_id', $tenant->id);

		// Published filter (optional)
		if ($request->filled('published')) {
			$query->where('published', $request->boolean('published') ? 1 : 0);
		}

		// Featured filter
		if ($request->boolean('featured')) {
			$query->where('featured', 1);
		}

		// Status filter (0 = قيد الإنشاء, 1 = منتهي, 2 = لم ينشأ بعد)
		if ($request->filled('status')) {
			$status = (int) $request->query('status');
			if (in_array($status, [0, 1, 2])) {
				$query->where('complete_status', $status);
			}
		}

		// Sort by created_at DESC
		$query->orderBy('created_at', 'desc');

		// Pagination with limit
        $perPage = min((int) $request->query('limit', 20), 50);
        $projects = $query->paginate($perPage);

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

        $items = collect($projects->items())->map(function ($project) use ($viewsBySlug) {
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
}

