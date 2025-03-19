<?php

namespace App\Http\Controllers\Api\project;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProjectStoreRequest;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectType;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\ProjectGalleryImg;
use App\Models\User\RealestateManagement\ProjectFloorplanImg;
use App\Models\User\RealestateManagement\ProjectSpecification;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request): JsonResponse
    {


        $projects = Project::with([
            'contents',
            'specifications',
            'types'
        ])->paginate(10);

        $formattedProjects = $projects->map(function ($project) {
            return [
                "id" => $project->id,
                "featured_image" => $project->featured_image ? "/storage/" . $project->featured_image : "/storage/default.jpg",
                "price_range" => "From $" . number_format($project->min_price, 2) . " to $" . number_format($project->max_price, 2),
                "latitude" => $project->latitude,
                "longitude" => $project->longitude,
                "featured" => $project->featured,
                "status" => $project->complete_status ?? "Unknown",
                "units" => $project->units ?? 0,
                "completion_date" => $project->completion_date ?? "N/A",
                "developer" => $project->developer ?? "Unknown",
                "published" => $project->published,
                "created_at" => $project->created_at,
                "updated_at" => $project->updated_at,

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
        });

        return response()->json([
            "status" => "success",
            "data" => [
                "projects" => $formattedProjects,
                "pagination" => [
                    "total" => $projects->total(),
                    "per_page" => $projects->perPage(),
                    "current_page" => $projects->currentPage(),
                    "last_page" => $projects->lastPage(),
                    "from" => $projects->firstItem(),
                    "to" => $projects->lastItem(),
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
            "featured_image" => $project->featured_image ? "/storage/" . $project->featured_image : "/storage/default.jpg",
            "price_range" => "From $" . number_format($project->min_price, 2) . " to $" . number_format($project->max_price, 2),
            "latitude" => $project->latitude,
            "longitude" => $project->longitude,
            "featured" => $project->featured,
            "status" => $project->complete_status ?? "Unknown",
            "units" => $project->units ?? 0,
            "completion_date" => $project->completion_date ?? "N/A",
            "developer" => $project->developer ?? "Unknown",
            "published" => $project->published,
            "created_at" => $project->created_at,
            "updated_at" => $project->updated_at,

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
                return "/storage/" . $image->image;
            }),

            "floorplan_images" => $project->floorplanImages->map(function ($image) {
                return "/storage/" . $image->image;
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

            "amenities" => $project->amenities->map(function ($propertyAmenity) {
                return $propertyAmenity->amenity ? [
                    "id" => $propertyAmenity->amenity->id,
                    "name" => $propertyAmenity->amenity->name,
                    "icon" => $propertyAmenity->amenity->icon ? "/storage/" . $propertyAmenity->amenity->icon : null,
                    "status" => $propertyAmenity->amenity->status
                ] : null;
            })->filter()->values()
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
        // Log::info($request->all());

        $validatedData = $request->validate([
            'featured_image' => 'nullable|string',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'featured' => 'required|boolean',
            'complete_status' => 'required|string',
            'units' => 'required|integer',
            'completion_date' => 'required|date',
            'developer' => 'required|string|max:255',
            'published' => 'required|boolean',
            'contents' => 'required|array',
            'gallery_images' => 'nullable|array',
            'floorplan_images' => 'nullable|array',
            'specifications' => 'nullable|array',
            'types' => 'nullable|array',
        ]);

 

        $userId = auth()->id();

        $project = Project::storeProject($userId, $validatedData);


        foreach ($validatedData['contents'] as $content) {
            $content['project_id'] = $project->id;
            ProjectContent::storeProjectContent($userId, $content);
        }

        if (!empty($validatedData['types'])) {
            foreach ($validatedData['types'] as $type) {
                $type['project_id'] = $project->id;
                ProjectType::storeProjectType($userId, $type);
            }
        }

        $responseProject = [
            'id' => $project->id,
            'title' => $validatedData['contents'][0]['title'],
            'address' => $validatedData['contents'][0]['address'],
            'min_price' => $project->min_price,
            'max_price' => $project->max_price,
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'featured' => (bool) $project->featured,
            'developer' => $request->developer,
            'completion_date' => $request->completion_date,
            'complete_status' => $request->complete_status,
            'units' => $request->units,
            'published' => (bool) $request->published,
            'featured_image' => $project->featured_image,
            'gallery_images' => $validatedData['gallery_images'] ?? [],
            'floorplan_images' => $validatedData['floorplan_images'] ?? [],
            'specifications' => $validatedData['specifications'] ?? [],
            'contents' => $validatedData['contents'],
            'types' => $validatedData['types'] ?? [],
            'created_at' => $project->created_at->toISOString(),
            'updated_at' => $project->updated_at->toISOString(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'User Project created successfully',
            'data' => [
                'user_project' => $responseProject
            ]
        ], 201);
    }

    /**
     * Update an existing project.
     */
    public function update(Request $request, $id): JsonResponse
    {

        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'featured' => 'boolean',
            'complete_status' => 'nullable|string',
            'units' => 'required|integer',
            'completion_date' => 'nullable|date',
            'developer' => 'nullable|string',
            'published' => 'boolean',

            'contents' => 'required|array',
            'language_id' => 'required|exists:languages,id',
            'title' => 'required|string',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
            'meta_description' => 'nullable|string',

            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpeg,png,jpg',

            'floorplan_images' => 'nullable|array',
            'floorplan_images.*' => 'image|mimes:jpeg,png,jpg',

            'specifications' => 'nullable|array',
            'key' => 'required|string',
            'label' => 'required|string',
            'value' => 'required|string',

            'types' => 'nullable|array',
            'language_id' => 'required|exists:languages,id',
            'title' => 'required|string',
            'min_area' => 'required|numeric',
            'max_area' => 'required|numeric',
            'min_price' => 'required|numeric',
            'max_price' => 'required|numeric',
            'unit' => 'required|string',

            'amenities' => 'nullable|array',
            'amenities.*' => 'exists:user_amenities,id',
        ]);

        DB::beginTransaction();
        try {
            $project = Project::find($id);

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found'
                ], 404);
            }

            if ($request->hasFile('featured_image')) {
                if ($project->featured_image) {
                    Storage::disk('public')->delete($project->featured_image);
                }
                $validatedData['featured_image'] = $request->file('featured_image')->store('projects', 'public');
            } else {
                unset($validatedData['featured_image']);
            }

            $project->update($validatedData);

            $project->contents()->delete();
            foreach ($validatedData['contents'] as $contentData) {
                ProjectContent::create([
                    'user_id' => $validatedData['user_id'],
                    'project_id' => $project->id,
                    'language_id' => $contentData['language_id'],
                    'title' => $contentData['title'],
                    'slug' => str_slug($contentData['title']),
                    'address' => $contentData['address'] ?? null,
                    'description' => $contentData['description'] ?? null,
                    'meta_keyword' => $contentData['meta_keyword'] ?? null,
                    'meta_description' => $contentData['meta_description'] ?? null,
                ]);
            }

            if ($request->hasFile('gallery_images')) {
                foreach ($project->galleryImages as $image) {
                    Storage::disk('public')->delete($image->image);
                }
                $project->galleryImages()->delete();

                foreach ($request->file('gallery_images') as $image) {
                    $imagePath = $image->store('projects/gallery', 'public');
                    ProjectGalleryImg::create([
                        'user_id' => $validatedData['user_id'],
                        'project_id' => $project->id,
                        'image' => $imagePath,
                    ]);
                }
            }

            if ($request->hasFile('floorplan_images')) {
                foreach ($project->floorplanImages as $image) {
                    Storage::disk('public')->delete($image->image);
                }
                $project->floorplanImages()->delete();

                foreach ($request->file('floorplan_images') as $image) {
                    $imagePath = $image->store('projects/floorplans', 'public');
                    ProjectFloorplanImg::create([
                        'user_id' => $validatedData['user_id'],
                        'project_id' => $project->id,
                        'image' => $imagePath,
                    ]);
                }
            }

            $project->specifications()->delete();
            if (!empty($validatedData['specifications'])) {
                foreach ($validatedData['specifications'] as $spec) {
                    ProjectSpecification::create([
                        'user_id' => $validatedData['user_id'],
                        'project_id' => $project->id,
                        'key' => $spec['key'],
                        'label' => $spec['label'],
                        'value' => $spec['value'],
                    ]);
                }
            }

            $project->types()->delete();
            if (!empty($validatedData['types'])) {
                foreach ($validatedData['types'] as $type) {
                    ProjectType::create([
                        'user_id' => $validatedData['user_id'],
                        'project_id' => $project->id,
                        'language_id' => $type['language_id'],
                        'title' => $type['title'],
                        'min_area' => $type['min_area'],
                        'max_area' => $type['max_area'],
                        'min_price' => $type['min_price'],
                        'max_price' => $type['max_price'],
                        'unit' => $type['unit'],
                    ]);
                }
            }

            $project->amenities()->delete();
            if (!empty($validatedData['amenities'])) {
                foreach ($validatedData['amenities'] as $amenityId) {
                    PropertyAmenity::create([
                        'user_id' => $validatedData['user_id'],
                        'property_id' => $project->id,
                        'amenity_id' => $amenityId,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'data' => [
                    'project_id' => $project->id
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update project.',
                'error' => $e->getMessage()
            ], 500);
        }


    }

    /**
     * Delete a project.
     */
    public function destroy($id): JsonResponse
    {

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

            if (!$project) {
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

            $project->delete();

            DB::commit();

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

        return response()->json([
            "status" => "success",
            "message" => "Project featured status updated",
            "data" => [
                "featured" => $project->featured
            ]
        ]);
    }
}
