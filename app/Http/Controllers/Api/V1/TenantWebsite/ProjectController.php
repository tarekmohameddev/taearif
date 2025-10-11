<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;

class ProjectController extends Controller
{
	protected function resolveTenant(string $tenantId): User
	{
		return User::where('username', $tenantId)->firstOrFail();
	}

    public function index(Request $request, string $tenantId)
	{
		$tenant = $this->resolveTenant($tenantId);

		$query = Project::query()
			->with(['contents', 'galleryImages'])
			->where('user_id', $tenant->id);

		// Published filter (optional)
		if ($request->filled('published')) {
			$query->where('published', $request->boolean('published') ? 1 : 0);
		}

		// Featured filter
		if ($request->boolean('featured')) {
			$query->where('featured', 1);
		}

		// Sort by created_at DESC
		$query->orderBy('created_at', 'desc');

		// Pagination with limit
        $perPage = min((int) $request->query('limit', 20), 50);
        $projects = $query->paginate($perPage);

        $items = collect($projects->items())->map(function ($project) {
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
                'images' => $images,
                'videoUrl' => $project->video_url ?? null,
                'amenities' => is_array($project->amenities) ? $project->amenities : [],
                'location' => [
                    'lat' => $project->latitude ? (float) $project->latitude : null,
                    'lng' => $project->longitude ? (float) $project->longitude : null,
                    'address' => $content?->address ?? '',
                ],
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
		$tenant = $this->resolveTenant($tenantId);

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
			'amenities' => is_array($project->amenities) ? $project->amenities : [],
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
		];

		return response()->json(['project' => $data]);
	}
}

