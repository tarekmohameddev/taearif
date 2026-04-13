<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\UserDistrict;
use App\Services\PropertyTranslationService;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    use ResolvesTenant;

    protected PropertyTranslationService $translator;

    public function __construct(PropertyTranslationService $translator)
    {
        $this->translator = $translator;
    }

    public function index(Request $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($request, $tenantId);

        $title = $request->query('title');
        $perPage = min((int) $request->query('per_page', 20), 50);
        $currentPage = max((int) $request->query('page', 1), 1);

        // Query properties
        $propertiesQuery = Property::query()
            ->with(['contents', 'galleryImages', 'project.contents'])
            ->where('user_id', $tenant->id)
            ->where('status', 1);

        if ($title) {
            $propertiesQuery->whereHas('contents', function ($q) use ($title) {
                $q->where('title', 'like', "%{$title}%");
            });
        }

        $properties = $propertiesQuery->get();

        // Query projects
        $projectsQuery = Project::query()
            ->with(['contents', 'galleryImages', 'user', 'properties.contents', 'properties.galleryImages'])
            ->where('user_id', $tenant->id);

        if ($title) {
            $projectsQuery->whereHas('contents', function ($q) use ($title) {
                $q->where('title', 'like', "%{$title}%");
            });
        }

        $projects = $projectsQuery->get();

        // Format properties
        $formattedProperties = $this->formatProperties($properties, $tenant);

        // Format projects
        $formattedProjects = $this->formatProjects($projects);

        // Combine results
        $allResults = $formattedProperties->concat($formattedProjects);

        // Sort by created_at descending (newest first)
        $allResults = $allResults->sortByDesc(function ($item) {
            return $item['createdAt'] ?? '';
        })->values();

        // Manual pagination
        $total = $allResults->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($currentPage - 1) * $perPage;
        $paginatedResults = $allResults->slice($offset, $perPage)->values();

        $from = $total > 0 ? $offset + 1 : null;
        $to = $total > 0 ? min($offset + $perPage, $total) : null;

        return response()->json([
            'data' => $paginatedResults,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to,
            ],
        ]);
    }

    private function formatProperties(Collection $properties, $tenant): Collection
    {
        // Pre-load districts and cities to avoid N+1 queries
        $stateIds = $properties
            ->flatMap(fn($p) => $p->contents->pluck('state_id'))
            ->filter()
            ->unique()
            ->values();

        $districtsMap = UserDistrict::with('city')
            ->whereIn('id', $stateIds)
            ->get()
            ->keyBy('id');

        return $properties->map(function ($p) use ($districtsMap) {
            $content = optional($p->contents->first());
            $slug = $content?->slug;

            // district/city derivation using pre-loaded data
            $district = $content && $content->state_id && isset($districtsMap[$content->state_id])
                ? $districtsMap[$content->state_id]
                : null;
            $city = $district?->city;
            $districtStr = trim(implode(' - ', array_filter([$district->name_ar ?? null, $city->name_ar ?? null])));

            // images (full urls)
            $featured = $p->featured_image ? asset($p->featured_image) : null;
            $gallery = $p->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
            $images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

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
                'type' => 'property',
                'id' => (string) $p->id,
                'slug' => $slug,
                'title' => $content?->title ?? '',
                'district' => $districtStr,
                'price' => isset($p->price) ? formatNumberWithoutTrailingZeros($p->price) : '0',
                'bedrooms' => (int) ($p->beds ?? 0),
                'bathrooms' => (int) ($p->bath ?? 0),
                'area' => isset($p->area) ? formatNumberWithoutTrailingZeros($p->area) : '0',
                'propertyType' => $this->translator->translateType($p->property_type),
                'propertyType_en' => $p->property_type,
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
    }

    private function formatProjects(Collection $projects): Collection
    {
        return $projects->map(function ($project) {
            $content = optional($project->contents->first());
            $slug = $content?->slug;

            // Images (full urls)
            $featured = $project->featured_image ? asset($project->featured_image) : null;
            $gallery = $project->galleryImages->pluck('image')->map(fn($img) => asset($img))->toArray();
            $images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

            return [
                'type' => 'project',
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
                'amenities' => $this->getAmenitiesArray($project),
                'location' => [
                    'lat' => $project->latitude ? (float) $project->latitude : null,
                    'lng' => $project->longitude ? (float) $project->longitude : null,
                    'address' => $content?->address ?? '',
                ],
                'createdAt' => $project->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Get amenities array from project, ensuring it's always an array.
     */
    private function getAmenitiesArray($project): array
    {
        return $project->amenities ?? [];
    }
}
