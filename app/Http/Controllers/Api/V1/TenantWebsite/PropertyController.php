<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Services\GoogleAnalyticsService;

class PropertyController extends Controller
{
	protected function resolveTenant(string $tenantId): User
	{
		return User::where('username', $tenantId)->firstOrFail();
	}

    public function index(Request $request, string $tenantId, GoogleAnalyticsService $analytics)
	{
		$tenant = $this->resolveTenant($tenantId);

		$query = Property::query()
			->with(['contents', 'galleryImages'])
			->where('user_id', $tenant->id)
			->where('status', 1);

		// Filters
		if ($purpose = $request->query('purpose')) {
			$query->where('purpose', $purpose);
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
			case 'area_desc':
				$query->orderBy('area', 'desc');
				break;
			case 'featured_first':
				$query->orderBy('featured', 'desc')->orderBy('reorder_featured', 'desc')->orderBy('created_at', 'desc');
				break;
			default:
				$query->orderBy('featured', 'desc')->orderBy('reorder_featured', 'desc')->orderBy('created_at', 'desc');
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

        // Analytics: views by slug (last N days)
        $days = (int) $request->query('days', 30);
        $slugs = $properties->getCollection()
            ->map(fn ($p) => optional($p->contents->first())->slug)
            ->filter()
            ->values();
        $paths = [];
        foreach ($slugs as $slug) {
            $paths[] = "/property/{$slug}";
        }
        $viewsByPath = $analytics->getPageViewsForPaths($tenant->username, now()->subDays($days), now(), $paths);
        $viewsBySlug = [];
        foreach ($slugs as $slug) {
            $viewsBySlug[$slug] = (int) ($viewsByPath["/property/{$slug}"] ?? 0);
        }

        $items = $properties->getCollection()->map(function ($p) use ($viewsBySlug) {
            $content = optional($p->contents->first());
            $slug    = $content?->slug;

            // district/city derivation
            $district = $content && $content->state_id ? UserDistrict::find($content->state_id) : null;
            $city     = $district?->city;
            $districtStr = trim(implode(' - ', array_filter([$district->name_ar ?? null, $city->name_ar ?? null])));

            // images (full urls)
            $featured = $p->featured_image ? asset($p->featured_image) : null;
            $gallery  = $p->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
            $images   = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

            return [
                'id' => (string) $p->id,
                'slug' => $slug,
                'title' => $content?->title ?? '',
                'district' => $districtStr,
                'price' => isset($p->price) ? (string) $p->price : '0',
                'views' => (int) ($viewsBySlug[$slug] ?? 0),
                'bedrooms' => (int) ($p->beds ?? 0),
                'bathrooms' => (int) ($p->bath ?? 0),
                'area' => isset($p->area) ? (string) $p->area : '0',
                'type' => $p->type,
                'transactionType' => $p->purpose,
                'image' => $featured,
                'status' => $p->status ? 'available' : 'rented',
                'createdAt' => $p->created_at?->toISOString(),
                'description' => $content?->description ?? '',
                'features' => is_array($p->features) ? $p->features : [],
                'location' => [
                    'lat' => $p->latitude ? (float) $p->latitude : null,
                    'lng' => $p->longitude ? (float) $p->longitude : null,
                    'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
                ],
                'images' => $images,
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
		$tenant = $this->resolveTenant($tenantId);

		$property = Property::with([
			'category',
			'user',
			'contents',
			'galleryImages',
			'proertyAmenities.amenity',
			'UserPropertyCharacteristics',
			'building',
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

		$data = [
            'id' => (string) $property->id,
            'slug' => $content?->slug ?? '',
            'title' => $content?->title ?? '',
            'district' => $districtStr,
            'price' => isset($property->price) ? (string) $property->price : '0',
            'views' => 0,
            'bedrooms' => (int) ($property->beds ?? 0),
            'bathrooms' => (int) ($property->bath ?? 0),
            'area' => isset($property->area) ? (string) $property->area : '0',
            'type' => $property->type ?? '',
            'transactionType' => $property->purpose,
            'image' => $featured,
            'status' => $property->status ? 'available' : 'rented',
            'createdAt' => $property->created_at?->toISOString(),
            'description' => $content?->description ?? '',
            'features' => is_array($property->features) ? $property->features : [],
            'location' => [
                'lat' => $property->latitude ? (float) $property->latitude : null,
                'lng' => $property->longitude ? (float) $property->longitude : null,
                'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
            ],
            'images' => $images,
        ];

		// Merge in extended fields to mirror admin show response
		$characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];
		$extra = [
			'payment_method' => $property->payment_method,
			'pricePerMeter' => $property->pricePerMeter,
			'floor_planning_image' => collect($property->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
			'video_url' => $property->video_url ? asset($property->video_url) : null,
			'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
			'video_image' => $property->video_image ? asset($property->video_image) : null,
			'faqs' => $property->faqs ?? [],
			'building' => $property->building,
		];

		$data = array_merge($data, $extra, $characteristics);

		return response()->json(['property' => $data]);
	}
}


