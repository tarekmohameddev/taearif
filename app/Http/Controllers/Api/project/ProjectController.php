<?php

namespace App\Http\Controllers\Api\project;

use App\Support\Audit;
use App\Models\Membership;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\Api\ApiMenuItem;
use App\Support\TenantActivity;
use App\Models\User\BasicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ProjectStoreRequest;
use App\Models\User\RealestateManagement\Amenity;
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

        $projects = Project::with(['contents', 'specifications', 'types'])
            ->where('user_id', $user->id)
            ->paginate(10);

        // ===== GA4 (last 30 days by default) =====
        $tenantId  = $user->username;
        $days      = (int)$request->input('days', 30);
        $startDate = Carbon::now()->subDays($days);
        $endDate   = Carbon::now();

        // Collect all slugs on the current page (all languages/contents)
        $slugsPerProject = $projects->getCollection()->mapWithKeys(function ($project) {
            $slugs = $project->contents->pluck('slug')->filter()->values()->all();
            return [$project->id => $slugs];
        });

        // Build GA pagePaths: /{username}/project/{slug}
        $paths = [];
        foreach ($slugsPerProject as $slugs) {
            foreach ($slugs as $slug) {
                $paths[] = "/{$tenantId}/project/{$slug}";
            }
        }

        // One GA call for this page
        $viewsByPath = $analytics->getPageViewsForPaths($tenantId, $startDate, $endDate, $paths);

        // Sum views per project across its content slugs
        $visitsByProject = [];
        foreach ($slugsPerProject as $projectId => $slugs) {
            $sum = 0;
            foreach ($slugs as $slug) {
                $sum += $viewsByPath["/{$tenantId}/project/{$slug}"] ?? 0;
            }
            $visitsByProject[$projectId] = $sum;
        }

        // ===== Format response =====
        $formattedProjects = $projects->getCollection()->map(function ($project) use ($visitsByProject) {
            return [
                "id"              => $project->id,
                "visits"          => (int)($visitsByProject[$project->id] ?? 0),   // << here
                "featured_image"  => $project->featured_image ? asset($project->featured_image) : null,
                "price_range"     => number_format($project->min_price, 2),
                "latitude"        => $project->latitude,
                "longitude"       => $project->longitude,
                "featured"        => (bool) $project->featured,
                "complete_status" => $project->complete_status,
                "units"           => $project->units,
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
            'amenities.amenity'
        ])->find($id);

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Project not found'
            ], 404);
        }

        $formattedProject = [
            "id" => $project->id,
            "featured_image" => asset($project->featured_image),
            "price_range" => "From $" . number_format($project->min_price, 2) . " to $" . number_format($project->max_price, 2),
            "latitude" => $project->latitude,
            "longitude" => $project->longitude,
            "featured" => $project->featured,
            "complete_status" => $project->complete_status ?? "Unknown",
            "units" => $project->units ?? 0,
            "completion_date" => $project->completion_date ?? "N/A",
            "developer" => $project->developer ?? "Unknown",
            "published" => $project->published,
            "created_at" => $project->created_at,
            "updated_at" => $project->updated_at,
            "amenities" => $project->amenities,
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

    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Resolve tenant owner (tenant for tenant; tenant for employee)
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $ownerId = $owner->id;

        $membership = Membership::where('user_id', $ownerId)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->with('package')
            ->first();

        if (!$membership || !$membership->package) {
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

        $rules = [
            // 'title' => 'required|max:255',
            'featured_image' => 'required|string',

            'address' => 'nullable',
            'description' => 'nullable|min:15',
            'complete_status' => 'nullable',
            'units' => 'nullable|integer',
            'completion_date' => 'nullable|date',
            'developer' => 'nullable|max:255',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'nullable',
            'floorplan_images' => 'nullable|array',
            'floorplan_images.*' => 'nullable',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'label' => 'nullable|array',
            'value' => 'nullable|array',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        $project = null;

        DB::transaction(function () use ($request, $ownerId, $defaultLang, &$project) {
            $requestData = $request->all();
            $requestData['featured_image'] = asset($request->featured_image);

            $project = Project::storeProject($ownerId, $requestData);

            if ($request->has('gallery_images') && is_array($request->gallery_images)) {
                foreach ($request->gallery_images as $imgPath) {
                    ProjectGalleryImg::storeGalleryImage($ownerId, $project->id, $imgPath);
                }
            }

            if ($request->has('floorplan_images') && is_array($request->floorplan_images)) {
                foreach ($request->floorplan_images as $imgPath) {
                    ProjectFloorplanImg::storeFloorplanImage($ownerId, $project->id, $imgPath);
                }
            }

            $firstContent = $request->input('contents.0');

            $title = $firstContent['title'] ?? '';
            $address = $firstContent['address'] ?? '';
            $description = $firstContent['description'] ?? '';

            $content = [
                'project_id' => $project->id,
                'language_id' => $defaultLang->id,
                'title' => $title,
                'address' => $address,
                'description' => $description,
                'meta_keyword' => $request->meta_keyword,
                'meta_description' => $request->meta_description,
            ];
            ProjectContent::storeProjectContent($ownerId, $content);

            $labels = $request->input('label', []);
            $values = $request->input('value', []);

            foreach ($labels as $key => $label) {
                if (!empty($values[$key])) {
                    ProjectSpecification::storeSpecification($ownerId, [
                        'language_id' => $defaultLang->id,
                        'project_id' => $project->id,
                        'key' => $key,
                        'label' => $label,
                        'value' => $values[$key],
                    ]);
                }
            }

            $this->ensureProjectsMenuExistsForUser($ownerId); // Add projects menu item if not exists for the user

        });

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications',
            'types',

        ])->find($project->id);

        if ($responseProject->featured_image) {
            $responseProject->featured_image = asset($responseProject->featured_image);
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

        // Log the activity
        TenantActivity::emit($request, 'project.created', 'user_projects', $responseProject->id, null, [
            'id' => $responseProject->id, 'title' => optional($responseProject->contents->first())->title
        ]);
        Audit::project(
            $ownerId,
            $responseProject->id,
            'created',
            'Project created',
            [
                'after' => [
                    'id'        => $responseProject->id,
                    'featured'  => (bool) $responseProject->featured,
                    'min_price' => $responseProject->min_price,
                    'max_price' => $responseProject->max_price,
                ],
            ]
        );
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
     * Update an existing project.
     */

    public function update(Request $request, $id)
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

        $rules = [
            // 'title' => 'required|max:255',
            'featured_image' => 'required|string',

            'address' => 'nullable',
            'description' => 'nullable|min:15',
            'gallery_images' => 'sometimes|array',
            'gallery_images.*' => 'string',
            'floorplan_images' => 'sometimes|array',
            'floorplan_images.*' => 'string',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'sometimes',
            'status' => 'sometimes',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'label' => 'nullable|array',
            'value' => 'nullable|array',
            'complete_status' => 'nullable',
            'units' => 'nullable|integer',

        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $ownerId, $defaultLang, &$project) {
            $requestData = $request->all();
            $requestData['featured_image'] = $request->featured_image;
            $project->updateProject($requestData);
            if ($request->has('gallery_images')) {
                ProjectGalleryImg::where('project_id', $project->id)->delete();
                foreach ($request->gallery_images as $imgPath) {
                    ProjectGalleryImg::storeGalleryImage($ownerId, $project->id, $imgPath);
                }
            }
            if ($request->has('floorplan_images')) {
                ProjectFloorplanImg::where('project_id', $project->id)->delete();
                foreach ($request->floorplan_images as $imgPath) {
                    ProjectFloorplanImg::storeFloorplanImage($ownerId, $project->id, $imgPath);
                }
            }

            ProjectContent::where('project_id', $project->id)->delete();

            $contents = (array) $request->input('contents', []);
            foreach ($contents as $content) {
                ProjectContent::storeProjectContent($ownerId, [
                    'project_id' => $project->id,
                    'language_id' => $content['language_id'],
                    'title' => $content['title'],
                    'address' => $content['address'],
                    'description' => $content['description'],
                    'meta_keyword' => $content['meta_keyword'],
                    'meta_description' => $content['meta_description'],
                    'slug' => Str::slug($content['title']),
                ]);
            }

            ProjectSpecification::where('project_id', $project->id)->delete();

            $specifications = (array) $request->input('specifications', []);
            foreach ($specifications as $spec) {
                ProjectSpecification::storeSpecification($ownerId, [
                    'language_id' => $defaultLang->id,
                    'project_id' => $project->id,
                    'key' => $spec['key'],
                    'label' => $spec['label'],
                    'value' => $spec['value'],
                ]);
            }

            ProjectType::where('project_id', $project->id)->delete();

            if ($request->has('types')) {
                $types = (array) $request->types;
                foreach ($types as $type) {
                    ProjectType::storeProjectType($ownerId, [
                        'project_id' => $project->id,
                        'language_id' => $type['label'],
                        'title' => $type['title'],
                        'min_area' => $type['min_area'],
                        'max_area' => $type['max_area'],
                        'min_price' => $type['min_price'],
                        'max_price' => $type['max_price'],
                        'unit' => $type['unit'],
                    ]);
                }
            }


        });

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications',
            'types',
        ])->find($project->id);

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
                'amenities'
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

            $project->amenities()->delete();

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
    public function toggleFeatured($id): JsonResponse
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
