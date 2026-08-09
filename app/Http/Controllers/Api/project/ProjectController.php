<?php

namespace App\Http\Controllers\Api\project;

use App\Http\Requests\Api\Project\ToggleProjectFeaturedRequest;
use App\Support\Audit;
use App\Support\AuditContext;
use App\Models\Membership;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\Api\ApiMenuItem;
use App\Support\TenantActivity;
use App\Models\User\BasicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Api\Project\StoreProjectRequest;
use App\Http\Requests\Api\Project\UpdateProjectRequest;
use App\Jobs\WriteProjectAuditJob;
use App\Models\User\RealestateManagement\Amenity;
use App\Http\Resources\Api\ProjectPropertyResource;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\ProjectType;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\ProjectGalleryImg;
use App\Models\User\RealestateManagement\ProjectFloorplanImg;
use App\Models\User\RealestateManagement\ProjectSpecification;
use Carbon\Carbon;
use App\Services\GoogleAnalyticsService;
use App\Services\MembershipCacheService;
use App\Services\Project\ProjectPropertyService;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request, GoogleAnalyticsService $analytics): JsonResponse
    {
        $user = $request->user();

        // Resolve tenant owner and allow visibility for owner + employees
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $ownerId = (int) $owner->id;

        $allowedUserIds = [$ownerId];
        try {
            $cacheKey = "tenant_employees_{$ownerId}";
            $employeeIds = Cache::remember($cacheKey, 300, function () use ($ownerId) {
                return \App\Models\User::where('tenant_id', $ownerId)
                    ->where('account_type', 'employee')
                    ->pluck('id')
                    ->toArray();
            });
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {}

        $projects = Project::with(['contents', 'specifications', 'types', 'creator', 'properties.contents', 'properties.galleryImages'])
            ->withCount('properties')
            ->whereIn('user_id', $allowedUserIds)
            ->orderBy('id', 'desc')
            ->paginate(10);

        // ===== Get views from pageview_analytics table (synced from GA4) =====
        // OPTIMIZED: Query from local database instead of GA4 API for better performance
        $tenantId = $owner->username;
        $days = (int) $request->input('days', 30);
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();

        // Collect all slugs on the current page (all languages/contents)
        $slugsPerProject = $projects->getCollection()->mapWithKeys(function ($project) {
            $slugs = $project->contents->pluck('slug')->filter()->values()->all();
            return [$project->id => $slugs];
        });

        // Build pagePaths to match public URLs: /project/{slug}, /ar/project/{slug}, /en/project/{slug}
        $supportedLanguages = ['ar', 'en'];
        $paths = [];
        $slugToPaths = [];
        foreach ($slugsPerProject as $slugs) {
            foreach ($slugs as $slug) {
                $slugToPaths[$slug] = [];
                $withoutLang = "/project/{$slug}";
                $paths[] = $withoutLang;
                $slugToPaths[$slug][] = $withoutLang;
                foreach ($supportedLanguages as $lang) {
                    $withLang = "/{$lang}/project/{$slug}";
                    $paths[] = $withLang;
                    $slugToPaths[$slug][] = $withLang;
                }
            }
        }

        // Query from pageview_analytics table (much faster than GA4 API)
        $viewsByPath = [];
        if (!empty($paths)) {
            $viewsData = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->where('page_type', 'project')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->whereIn('page_path', $paths)
                ->select('page_path', DB::raw('SUM(views_count) as total_views'))
                ->groupBy('page_path')
                ->get()
                ->keyBy('page_path');

            // Build a map of path => views
            foreach ($viewsData as $path => $data) {
                $viewsByPath[$path] = (int) $data->total_views;
            }
        }

        // Sum views per project across its content slugs and language variations
        $visitsByProject = [];
        foreach ($slugsPerProject as $projectId => $slugs) {
            $sum = 0;
            foreach ($slugs as $slug) {
                foreach (($slugToPaths[$slug] ?? []) as $p) {
                    $sum += (int) ($viewsByPath[$p] ?? 0);
                }
            }
            $visitsByProject[$projectId] = $sum;
        }

        // ===== Format response =====
        $formattedProjects = $projects->getCollection()->map(function ($project) use ($visitsByProject) {
            return [
                "id"              => $project->id,
                "visits"          => (int)($visitsByProject[$project->id] ?? 0),   // << here
                "featured_image"  => $project->featured_image ? asset($project->featured_image) : null,
                "video_url"       => $this->resolveMediaUrl($project->video_url),
                "brochure"        => $this->resolveMediaUrl($project->brochure),
                "min_price"       => $project->min_price,
                "max_price"       => $project->max_price,
                "min_price_formatted" => $project->min_price !== null ? formatNumberWithoutTrailingZeros($project->min_price) : null,
                "max_price_formatted" => $project->max_price !== null ? formatNumberWithoutTrailingZeros($project->max_price) : null,
                "price_range"     => formatNumberWithoutTrailingZeros($project->min_price ?? 0),
                "latitude"        => $project->latitude,
                "longitude"       => $project->longitude,
                ...$this->projectLocationFields($project),
                "featured"        => (bool) $project->featured,
                "complete_status" => $project->complete_status,
                "units"           => $project->units, // legacy manual field; use units_count for dashboards
                "units_count"     => (int) $project->properties_count,
                "completion_date" => $project->completion_date,
                "developer"       => $project->developer,
                "published"       => (bool) $project->published,
                "created_at"      => $project->created_at->toISOString(),
                "updated_at"      => $project->updated_at->toISOString(),
                "amenities"       => $project->amenities ?? [],
                "contents"        => $project->contents->map(function ($content) {
                    return [
                        "id"               => $content->id,
                        "title"            => $content->title,
                        "address"          => $content->address,
                        "description"      => $content->description,
                        "meta_keyword"     => $content->meta_keyword,
                        "meta_description" => $content->meta_description,
                        "slug"             => $content->slug,
                    ];
                }),
                "specifications"  => $project->specifications->map(fn ($s) => [
                    "key" => $s->key, "label" => $s->label, "value" => $s->value,
                ]),
                "types"           => $project->types->map(fn ($t) => [
                    "title" => $t->title, "min_area" => $t->min_area, "max_area" => $t->max_area,
                    "min_price" => $t->min_price, "max_price" => $t->max_price, "unit" => $t->unit,
                ]),
                "creator"         => $project->creator ? [
                    "id"   => $project->creator->id,
                    "name" => trim(($project->creator->first_name ?? '') . ' ' . ($project->creator->last_name ?? '')) ?: ($project->creator->username ?? $project->creator->email),
                    "type" => $project->creator->account_type,
                ] : null,
                "properties"       => $project->properties->map(function ($property) {
                    return $this->formatProperty($property);
                }),
            ];
        });

        return response()->json([
            "status" => "success",
            "data" => [
                "projects" => $formattedProjects,
                "pagination" => [
                    "total"        => $projects->total(),
                    "per_page"     => $projects->perPage(),
                    "current_page" => $projects->currentPage(),
                    "last_page"    => $projects->lastPage(),
                    "from"         => $projects->firstItem(),
                    "to"           => $projects->lastItem(),
                ]
            ]
        ]);
    }


    /**
     * Get a single project.
     */
    public function show($id): JsonResponse
    {

        $project = Project::with([
            'contents',
            'galleryImages',
            'floorplanImages',
            'specifications',
            'types',
            'user',  
            'creator',
            'properties.contents',
            'properties.galleryImages',
        ])
            ->withCount('properties')
            ->find($id);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found'
            ], 404);
        }

        // Get views from pageview_analytics table (synced from GA4)
        // OPTIMIZED: Query from local database instead of GA4 API for better performance
        $visits = 0;
        if ($project->user && $project->contents->first()) {
            $days = (int) request()->query('days', 30);
            $tenantId = $project->user->username;
            
            // Get all slugs for this project (multi-language support)
            $slugs = $project->contents->pluck('slug')->filter()->values()->all();
            
            if (!empty($slugs)) {
                $startDate = Carbon::today()->subDays($days)->toDateString();
                $endDate = Carbon::today()->toDateString();
                
                // Build paths for all slug variants
                $paths = [];
                foreach ($slugs as $slug) {
                    $paths[] = "/project/{$slug}";
                    $paths[] = "/ar/project/{$slug}";
                    $paths[] = "/en/project/{$slug}";
                }

                // Query from pageview_analytics table
                $viewsData = DB::table('pageview_analytics')
                    ->where('tenant_id', $tenantId)
                    ->where('page_type', 'project')
                    ->whereBetween('date_bucket', [$startDate, $endDate])
                    ->whereIn('page_path', $paths)
                    ->select('page_path', DB::raw('SUM(views_count) as total_views'))
                    ->groupBy('page_path')
                    ->get();

                // Sum views across all path variants
                foreach ($viewsData as $data) {
                    $visits += (int) $data->total_views;
                }
            }
        }

        $formattedProject = [
            "id" => $project->id,
            "visits" => $visits,
            "featured_image" => asset($project->featured_image),
            "video_url" => $this->resolveMediaUrl($project->video_url),
            "brochure" => $this->resolveMediaUrl($project->brochure),
            "min_price" => $project->min_price,
            "max_price" => $project->max_price,
            "min_price_formatted" => $project->min_price !== null ? formatNumberWithoutTrailingZeros($project->min_price) : null,
            "max_price_formatted" => $project->max_price !== null ? formatNumberWithoutTrailingZeros($project->max_price) : null,
            "price_range" => "From $" . formatNumberWithoutTrailingZeros($project->min_price ?? 0) . " to $" . formatNumberWithoutTrailingZeros($project->max_price ?? 0),
            "latitude" => $project->latitude,
            "longitude" => $project->longitude,
            ...$this->projectLocationFields($project),
            "featured" => $project->featured,
            "complete_status" => $project->complete_status ?? "Unknown",
            "units" => $project->units ?? 0,
            "units_count" => (int) $project->properties_count,
            "units_display_only" => $project->units ?? 0,
            "completion_date" => $project->completion_date ?? "N/A",
            "developer" => $project->developer ?? "Unknown",
            "published" => $project->published,
            "created_at" => $project->created_at,
            "updated_at" => $project->updated_at,
            "amenities" => $project->amenities ?? [],
            "contents" => $project->contents->map(function ($content) {
                return [
                    "id" => $content->id,
                    "title" => $content->title,
                    "address" => $content->address,
                    "description" => $content->description,
                    "meta_keyword" => $content->meta_keyword,
                    "meta_description" => $content->meta_description,
                ];
            }),

            "gallery" => $project->galleryImages->map(function ($image) {
                return  asset($image->image);
            }),

            "floorplan_images" => $project->floorplanImages->map(function ($image) {
                return  asset($image->image);
            }),

            "specifications" => $project->specifications->map(function ($spec) {
                return [
                    "key" => $spec->key,
                    "label" => $spec->label,
                    "value" => $spec->value,
                ];
            }),

            "types" => $project->types->map(function ($type) {
                return [
                    "title" => $type->title,
                    "min_area" => $type->min_area,
                    "max_area" => $type->max_area,
                    "min_price" => $type->min_price,
                    "max_price" => $type->max_price,
                    "unit" => $type->unit,
                ];
            }),
            "creator" => $project->creator ? [
                "id"   => $project->creator->id,
                "name" => trim(($project->creator->first_name ?? '') . ' ' . ($project->creator->last_name ?? '')) ?: ($project->creator->username ?? $project->creator->email),
                "type" => $project->creator->account_type,
            ] : null,
            "properties" => $project->properties->map(function ($property) {
                return $this->formatProperty($property);
            }),
        ];

        return response()->json([
            "status" => "success",
            "data" => [
                "project" => $formattedProject
            ]
        ]);
    }

    /**
     * Store a new project.
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */

    public function store(StoreProjectRequest $request)
    {
        $user = auth()->user();

        // Resolve tenant owner (tenant for tenant; tenant for employee)
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $ownerId = $owner->id;

        // Check if user has active membership (cached)
        $membership = MembershipCacheService::getActiveMembership($ownerId);


        if (!($membership instanceof Membership) || !$membership->package) {
            return response()->json([
                'status' => 'fail',
                'message' => 'No active package found for the user.',
            ], 403);
        }

        $projectLimit = $membership->package->project_limit_number;
        $currentProjectsCount = Project::where('user_id', $ownerId)->count();

        if (!is_null($projectLimit) && $currentProjectsCount >= $projectLimit) {
            return response()->json([
                'status' => false,
                'message' => 'You have reached your project creation limit.',
                'limit' => $projectLimit,
                'used' => $currentProjectsCount
            ], 403);
        }


        $defaultLang = Language::where('user_id', $ownerId)->where('is_default', 1)->firstOrFail();

        $project = null;

        DB::transaction(function () use ($request, $ownerId, $defaultLang, &$project) {
            $requestData = $this->mergeRequestLocation($request->all());
            $requestData['featured_image'] = asset($request->featured_image);
            $requestData['video_url'] = !empty($request->video_url) ? $request->video_url : null; // Handle empty string
            $requestData['brochure'] = !empty($request->brochure) ? $request->brochure : null;
            $requestData['amenities'] = $this->normalizeAmenities($request->input('amenities'));

            // Suppress ProjectObserver::created — audit is written once via queued job after commit.
            $project = Project::withoutEvents(function () use ($ownerId, $requestData) {
                return Project::storeProject($ownerId, $requestData, auth()->id());
            });

            $galleryImages = $request->has('gallery_images') && is_array($request->gallery_images)
                ? array_values($request->gallery_images)
                : [];
            ProjectGalleryImg::insertManyForProject($ownerId, $project->id, $galleryImages);

            $floorplanImages = $request->has('floorplan_images') && is_array($request->floorplan_images)
                ? array_values($request->floorplan_images)
                : [];
            ProjectFloorplanImg::insertManyForProject($ownerId, $project->id, $floorplanImages);

            ProjectContent::insertManyForProject(
                $ownerId,
                $project->id,
                (array) $request->input('contents', [])
            );

            ProjectSpecification::insertManyForProject(
                $ownerId,
                $project->id,
                (int) $defaultLang->id,
                (array) $request->input('specifications', [])
            );

            if ($request->has('types')) {
                ProjectType::insertManyForProject(
                    $ownerId,
                    $project->id,
                    (int) $defaultLang->id,
                    (array) $request->types
                );
            }

            $auditCtx = AuditContext::data();
            $projectAttributes = $project->getAttributes();

            DB::afterCommit(function () use ($ownerId, $project, $auditCtx, $projectAttributes) {
                WriteProjectAuditJob::dispatch([
                    'tenant_id' => $auditCtx['tenant_id'] ?? $ownerId,
                    'project_id' => $project->id,
                    'actor_id' => $auditCtx['actor_id'] ?? null,
                    'actor_type' => $auditCtx['actor_type'] ?? 'tenant',
                    'ip_address' => $auditCtx['ip_address'] ?? null,
                    'user_agent' => $auditCtx['user_agent'] ?? null,
                    'changes' => ['after' => $projectAttributes],
                    'attributes' => $projectAttributes,
                ]);
            });
        });

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project creation failed',
            ], 500);
        }

        $this->ensureProjectsMenuExistsForUser($ownerId);

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications',
            'types',

        ])->findOrFail($project->id);

        if ($responseProject->featured_image) {
            $responseProject->featured_image = asset($responseProject->featured_image);
        }

        if ($responseProject->brochure) {
            $responseProject->brochure = $this->resolveMediaUrl($responseProject->brochure);
        }

        $responseProject->gallery_images = $responseProject->galleryImages->map(function ($image) {
            $image->image = asset($image->image);
            return $image;
        });

        $responseProject->floor_plan_images = $responseProject->floorPlanImages->map(function ($image) {
            $image->image = asset($image->image);
            return $image;
        });

        $responseProject->featured = (bool) $responseProject->featured;

        // Add formatted price fields
        $responseProject->min_price_formatted = $responseProject->min_price !== null 
            ? formatNumberWithoutTrailingZeros($responseProject->min_price) 
            : null;
        $responseProject->max_price_formatted = $responseProject->max_price !== null 
            ? formatNumberWithoutTrailingZeros($responseProject->max_price) 
            : null;

        $this->appendProjectLocationAlias($responseProject);

        // Log the activity (sync — do not queue)
        TenantActivity::emit($request, 'project.created', 'user_projects', $responseProject->id, null, [
            'id' => $responseProject->id, 'title' => optional($responseProject->contents->first())->title
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'user_project' => $responseProject
        ], 201);
    }

    /**
     * Ensure the "Projects" menu exists for the user.
     * If it doesn't exist, create it.
     */

    /**
     * Normalize amenities input to array format.
     * Handles string (JSON or plain), array, or null/empty input.
     */
    private function normalizeAmenities($value): array
    {
        if (is_string($value)) {
            // Try to decode as JSON first
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // If it's a comma-separated string, split it
            if (strpos($value, ',') !== false) {
                return array_values(array_filter(array_map('trim', explode(',', $value))));
            }
            // If it's a plain string, wrap it in an array
            return [trim($value)];
        } elseif (is_array($value)) {
            // Filter out empty/null values and reindex
            return array_values(array_filter($value, fn($item) => $item !== null && $item !== ''));
        }
        // Return empty array for null/empty input
        return [];
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

    private function mergeRequestLocation(array $requestData): array
    {
        $location = app(ProjectPropertyService::class)
            ->normalizeLocation($requestData, required: false);

        return array_merge($requestData, $location);
    }

    private function projectLocationFields(Project $project): array
    {
        return [
            'city_id' => $project->city_id,
            'state_id' => $project->state_id,
            'district_id' => $project->state_id,
        ];
    }

    private function appendProjectLocationAlias(Project $project): Project
    {
        $project->district_id = $project->state_id;

        return $project;
    }

    private function ensureProjectsMenuExistsForUser($userId)
    {
        $exists = ApiMenuItem::where('user_id', $userId)
            ->where('url', '/projects')
            ->exists();

        if (!$exists) {
            $maxOrder = ApiMenuItem::where('user_id', $userId)->max('order') ?? 0;

            ApiMenuItem::create([
                'user_id' => $userId,
                'label' => 'المشاريع',
                'url' => '/projects',
                'is_external' => false,
                'is_active' => true,
                'order' => $maxOrder + 1,
                'parent_id' => null,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
            ]);
        }
    }

    /**
     * Format a property with all its details for API response.
     */
    private function formatProperty($property): array
    {
        return (new ProjectPropertyResource($property))->resolve();
    }


    /**
     * Update an existing project.
     */

    public function update(UpdateProjectRequest $request, $id)
    {
        $user = auth()->user();

        // Resolve tenant owner (tenant for tenant; tenant for employee)
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $ownerId = $owner->id;

        $defaultLang = Language::where('user_id', $ownerId)->where('is_default', 1)->firstOrFail();

        // Allow updating projects owned by tenant or any employee
        $allowedUserIds = [$ownerId];
        try {
            $employeeIds = \App\Models\User::where('tenant_id', $ownerId)->pluck('id')->toArray();
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {}

        $project = Project::whereIn('user_id', $allowedUserIds)->where('id', $id)->first();
        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found for this tenant',
            ], 404);
        }

        DB::transaction(function () use ($request, $ownerId, $defaultLang, &$project) {
            $requestData = $this->mergeRequestLocation($request->all());
            $requestData['featured_image'] = $request->featured_image;
            $requestData['video_url'] = !empty($request->video_url) ? $request->video_url : null; // Handle empty string
            if ($request->has('brochure')) {
                $requestData['brochure'] = !empty($request->brochure) ? $request->brochure : null;
            }
            $requestData['amenities'] = $this->normalizeAmenities($request->input('amenities', $project->amenities));

            $project->updateProject($requestData);

            if ($request->has('gallery_images')) {
                ProjectGalleryImg::where('project_id', $project->id)->delete();
                $galleryImages = is_array($request->gallery_images)
                    ? array_values($request->gallery_images)
                    : [];
                ProjectGalleryImg::insertManyForProject($ownerId, $project->id, $galleryImages);
            }

            if ($request->has('floorplan_images')) {
                ProjectFloorplanImg::where('project_id', $project->id)->delete();
                $floorplanImages = is_array($request->floorplan_images)
                    ? array_values($request->floorplan_images)
                    : [];
                ProjectFloorplanImg::insertManyForProject($ownerId, $project->id, $floorplanImages);
            }

            ProjectContent::where('project_id', $project->id)->delete();
            ProjectContent::insertManyForProject(
                $ownerId,
                $project->id,
                (array) $request->input('contents', [])
            );

            ProjectSpecification::where('project_id', $project->id)->delete();
            ProjectSpecification::insertManyForProject(
                $ownerId,
                $project->id,
                (int) $defaultLang->id,
                (array) $request->input('specifications', [])
            );

            ProjectType::where('project_id', $project->id)->delete();
            if ($request->has('types')) {
                ProjectType::insertManyForProject(
                    $ownerId,
                    $project->id,
                    (int) $defaultLang->id,
                    (array) $request->types
                );
            }
        });

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications',
            'types',
        ])->find($project->id);

        // Add formatted price fields
        $responseProject->min_price_formatted = $responseProject->min_price !== null 
            ? formatNumberWithoutTrailingZeros($responseProject->min_price) 
            : null;
        $responseProject->max_price_formatted = $responseProject->max_price !== null 
            ? formatNumberWithoutTrailingZeros($responseProject->max_price) 
            : null;

        if ($responseProject->brochure) {
            $responseProject->brochure = $this->resolveMediaUrl($responseProject->brochure);
        }

        $this->appendProjectLocationAlias($responseProject);

        TenantActivity::emit($request, 'project.created', 'user_projects', $responseProject->id, null, [
            'id' => $responseProject->id, 'title' => optional($responseProject->contents->first())->title
        ]);

        $after = $responseProject->toArray();
        Audit::project(
            $ownerId,
            $responseProject->id,
            'updated',
            'Project updated',
            ['after' => $after]
        );
        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'user_project' => $responseProject
        ]);
    }

    /**
     * Delete a project.
     */
    public function destroy($id): JsonResponse
    {
        $userId = auth()->id();
        DB::beginTransaction();
        try {
            $project = Project::with([
                'contents',
                'galleryImages',
                'floorplanImages',
                'specifications',
                'types',
            ])->find($id);


            if (!$project || $project->user_id != $userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], 404);
            }

            $project->contents()->delete();

            foreach ($project->galleryImages as $image) {
                if ($image->image) {
                    \Storage::disk('public')->delete($image->image);
                }
            }
            $project->galleryImages()->delete();

            foreach ($project->floorplanImages as $image) {
                if ($image->image) {
                    \Storage::disk('public')->delete($image->image);
                }
            }
            $project->floorplanImages()->delete();

            $project->specifications()->delete();

            $project->types()->delete();

            if ($project->featured_image) {
                \Storage::disk('public')->delete($project->featured_image);
            }

            Audit::project(
                $userId,
                $project->id,
                'deleted',
                'Project deleted',
                ['before' => $project->toArray()]
            );

            $project->delete();

            DB::commit();

            // TenantActivity::emit($request, 'project.deleted', 'user_projects', $id, $project->toArray(), null);

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete project.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle project featured status.
     */
    public function toggleFeatured(ToggleProjectFeaturedRequest $request, $id): JsonResponse
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found'
            ], 404);
        }

        $project->featured = !$project->featured;
        $project->save();


        // Audit::project(
        //     (int) $project->user_id,
        //     (int) $project->id,
        //     'toggle_featured',
        //     'Toggled project featured',
        //     ['featured' => (bool) $project->featured]
        // );

        return response()->json([
            "status" => "success",
            "message" => "Project featured status updated",
            "data" => [
                "featured" => $project->featured
            ]
        ]);
    }

    public function propertyCounters(int $id): JsonResponse
    {
        $project = Project::query()->find($id);
        if (! $project) {
            return response()->json(['status' => 'error', 'message' => 'Project not found'], 404);
        }

        $base = Property::query()->where('project_id', $id);
        $counts = [
            'total' => (clone $base)->count(),
            'available' => (clone $base)->where('unit_status', 'available')->count(),
            'reserved' => (clone $base)->where('unit_status', 'reserved')->count(),
            'sold' => (clone $base)->where('unit_status', 'sold')->count(),
            'rented' => (clone $base)->where('unit_status', 'rented')->count(),
            'sale_units' => (clone $base)->where('listing_purpose', 'sale')->count(),
            'rent_units' => (clone $base)->where('listing_purpose', 'rent')->count(),
        ];

        $byCategory = (clone $base)
            ->join('api_user_categories', 'user_properties.category_id', '=', 'api_user_categories.id')
            ->selectRaw('api_user_categories.name, COUNT(*) as total')
            ->groupBy('api_user_categories.name')
            ->pluck('total', 'name')
            ->all();

        if (! empty($byCategory)) {
            $counts['by_category'] = $byCategory;
        }

        foreach (['available', 'reserved', 'sold', 'rented', 'sale_units', 'rent_units'] as $key) {
            if (($counts[$key] ?? 0) === 0) {
                unset($counts[$key]);
            }
        }

        return response()->json(['status' => 'success', 'data' => $counts]);
    }

    public function userProjects(Request $request)
    {
        $user = $request->user();

        Log::info('User ID: ' . $user->id);

        $projects = Project::where('user_id', $user->id)
            ->with(['projectContents' => function ($query) {
                $query->select('id', 'project_id', 'title')->orderBy('id');
            }])
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'title' => optional($project->projectContents->first())->title ?? '(No title)'
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_projects' => $projects
            ]
        ]);
    }
}
