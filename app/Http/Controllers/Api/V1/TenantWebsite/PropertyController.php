<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Services\GoogleAnalyticsService;
use App\Services\PropertyTranslationService;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;

class PropertyController extends Controller
{
    use ResolvesTenant;

	protected PropertyTranslationService $translator;

	public function __construct(PropertyTranslationService $translator)
	{
		$this->translator = $translator;
	}

    public function index(Request $request, string $tenantId, GoogleAnalyticsService $analytics)
	{
		$tenant = $this->resolveTenant($request, $tenantId);

		$query = Property::query()
			->with(['contents', 'galleryImages', 'project.contents'])
			->where('user_id', $tenant->id)
			->where('status', 1);

		// Filters
		if ($purpose = $request->query('purpose')) {
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
		if ($q = $request->query('q')) {
			$query->whereHas('contents', function ($qbuilder) use ($q) {
				$qbuilder->where('title', 'like', "%{$q}%")
					->orWhere('address', 'like', "%{$q}%");
			});
		}
		foreach (['type','beds','bath','city_id','state_id','category_id','project_id'] as $eq) {
			if (!is_null($request->query($eq))) {
				$field = in_array($eq, ['city_id','state_id','category_id']) ? $eq : $eq; // clarity
				if (in_array($eq, ['city_id','state_id','category_id'])) {
					$query->whereHas('contents', function ($qbuilder) use ($eq, $request) {
						$qbuilder->where($eq, $request->query($eq));
					});
				} else {
					$query->where($eq, $request->query($eq));
				}
			}
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

        // Analytics: views by slug (last N days)
        $days = (int) $request->query('days', 30);

        // Build paths and slugs map in single pass
        // Support both with and without language prefixes
        $paths = [];
        $slugToPathsMap = [];  // slug => array of paths
        $supportedLanguages = ['ar', 'en'];  // Supported language prefixes

        foreach ($properties->getCollection() as $p) {
            $content = $p->contents->first();
            if ($content && $content->slug) {
                $slug = $content->slug;
                $slugToPathsMap[$slug] = [];

                // Add path without language prefix
                $pathWithoutLang = "/property/{$slug}";
                $paths[] = $pathWithoutLang;
                $slugToPathsMap[$slug][] = $pathWithoutLang;

                // Add paths with language prefixes
                foreach ($supportedLanguages as $lang) {
                    $pathWithLang = "/{$lang}/property/{$slug}";
                    $paths[] = $pathWithLang;
                    $slugToPathsMap[$slug][] = $pathWithLang;
                }
            }
        }

        // Get view counts from Google Analytics
        // Use backend filtering to get ALL data (including historical), not just recent tenant-filtered data
        $viewsByPath = [];
        if (!empty($paths)) {
            try {
                // Use getAllAnalyticsWithFilters to query all data, then filter by paths
                // This includes historical data with empty tenant_id that will be derived from slug
                $allData = $analytics->getAllAnalyticsWithFilters(
                    now()->subDays($days),
                    now(),
                    [
                        'tenant_ids' => [$tenant->username],  // Filter by this tenant
                        'exclude_empty_tenant' => false,      // Include old data (will be matched by slug)
                        'limit' => count($paths) * 10,        // Get more to ensure we capture all variants
                    ]
                );

                // Build a map of path => views from all returned data
                foreach ($allData['data'] as $item) {
                    $path = $item['path'];
                    $views = (int) $item['views'];
                    if (in_array($path, $paths)) {
                        $viewsByPath[$path] = ($viewsByPath[$path] ?? 0) + $views;
                    }
                }

                // Optional: Log for debugging (remove in production)
                if ($request->boolean('debug_views')) {
                    \Log::info('GA Views Debug', [
                        'tenant' => $tenant->username,
                        'paths_requested' => $paths,
                        'views_received' => $viewsByPath,
                        'days' => $days,
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but continue without views
                \Log::error('Google Analytics error in PropertyController', [
                    'tenant' => $tenant->username,
                    'error' => $e->getMessage(),
                    'paths_count' => count($paths),
                ]);
            }
        }

        // Map views to slugs - Sum views from all language variations
        $viewsBySlug = [];
        foreach ($slugToPathsMap as $slug => $pathVariations) {
            $totalViews = 0;
            foreach ($pathVariations as $path) {
                $totalViews += (int) ($viewsByPath[$path] ?? 0);
            }
            $viewsBySlug[$slug] = $totalViews;
        }

		$items = $properties->getCollection()->map(function ($p) use ($viewsBySlug, $districtsMap) {
            $content = optional($p->contents->first());
            $slug    = $content?->slug;

            // district/city derivation using pre-loaded data
            $district = $content && $content->state_id && isset($districtsMap[$content->state_id])
                ? $districtsMap[$content->state_id]
                : null;
            $city     = $district?->city;
            $districtStr = trim(implode(' - ', array_filter([$district->name_ar ?? null, $city->name_ar ?? null])));

            // images (full urls)
            $featured = $p->featured_image ? asset($p->featured_image) : null;
            $gallery  = $p->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
            $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));


			// Normalize purpose for public API and map availability
			$normalizedPurpose = match ($p->purpose) {
				'rented' => 'rent',
				'sold' => 'sale',
				default => $p->purpose,
			};
			$isUnavailable = in_array($p->purpose, ['rented', 'sold'], true);

			// Get project data if relationship is loaded
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
                'views' => (int) ($viewsBySlug[$slug] ?? 0),
                'bedrooms' => (int) ($p->beds ?? 0),
                'bathrooms' => (int) ($p->bath ?? 0),
                'area' => isset($p->area) ? formatNumberWithoutTrailingZeros($p->area) : '0',
                'type' => $this->translator->translateType($p->type),
				'type_en' => $p->type,
				'transactionType' => $this->translator->translatePurpose($normalizedPurpose),
				'transactionType_en' => $normalizedPurpose,
                'image' => $featured,
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
        $district = $content && $content->state_id ? UserDistrict::find($content->state_id) : null;
        $city     = $district?->city;
        $districtStr = trim(implode(' - ', array_filter([$district->name_ar ?? null, $city->name_ar ?? null])));

        $featured = $property->featured_image ? asset($property->featured_image) : null;
        $gallery  = $property->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
        $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        // Fetch views from Google Analytics
        $views = 0;
        if ($content && $content->slug) {
            try {
                $analytics = app(\App\Services\GoogleAnalyticsService::class);
                $days = (int) $request->query('days', 30);
                
                // Build paths for this property (with and without language prefixes)
                $paths = [
                    "/property/{$content->slug}",
                    "/ar/property/{$content->slug}",
                    "/en/property/{$content->slug}",
                ];

                $allData = $analytics->getAllAnalyticsWithFilters(
                    now()->subDays($days),
                    now(),
                    [
                        'tenant_ids' => [$tenant->username],
                        'exclude_empty_tenant' => false,
                        'limit' => count($paths) * 10,
                    ]
                );

                // Sum views across all path variants
                foreach ($allData['data'] as $item) {
                    if (in_array($item['path'], $paths)) {
                        $views += (int) $item['views'];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Google Analytics error in PropertyController show', [
                    'tenant' => $tenant->username,
                    'slug' => $slug,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
            'type' => $this->translator->translateType($property->type),
            'type_en' => $property->type ?? '',
            'transactionType' => $this->translator->translatePurpose($property->purpose),
            'transactionType_en' => $property->purpose,
            'image' => $featured,
            'status' => $property->status ? 'available' : 'rented',
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
}


