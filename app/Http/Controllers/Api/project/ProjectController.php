<?php

namespace App\Http\Controllers\Api\project;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\ProjectStoreRequest;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectType;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\ProjectGalleryImg;
use App\Models\User\RealestateManagement\ProjectFloorplanImg;
use App\Models\User\RealestateManagement\ProjectSpecification;
use Illuminate\Support\Facades\Validator;


class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request): JsonResponse
    {

        $user = $request->user();
        $projects = Project::with([
            'contents',
            'specifications',
            'types'
        ])->where('user_id', $user->id)->paginate(10);
        

        $formattedProjects = $projects->map(function ($project) {
            return [
                "id" => $project->id,
                "featured_image" => asset($project->featured_image),
                "price_range" =>  number_format($project->min_price, 2),
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
            "featured_image" => asset($project->featured_image),
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
                return  $image->image;
            }),

            "floorplan_images" => $project->floorplanImages->map(function ($image) {
                return  $image->image;
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
         $validator = Validator::make($request->all(), [
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
             'amenities' => 'nullable|array',
         ]);
     
         if ($validator->fails()) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Validation failed',
                 'errors' => $validator->errors()
             ], 422);
         }
     
         $validatedData = $validator->validated();
         $userId = auth()->id();
         $userLanguage = Language::where('user_id', $userId)->where('is_default', 1)->first();
         $languageId = $userLanguage ? $userLanguage->id : 1;
     
         DB::beginTransaction();
        // try {
             $project = Project::storeProject($userId, $validatedData);
     
             foreach ($validatedData['contents'] as $content) {
                 $content['project_id'] = $project->id;
                 $content['slug'] = Str::slug($content['title']);
                 ProjectContent::storeProjectContent($userId, $content);
             }
     
             if (!empty($validatedData['types'])) {
                 foreach ($validatedData['types'] as $type) {
                     $type['project_id'] = $project->id;
                     ProjectType::storeProjectType($userId, $type);
                 }
             }
     
            //  if (!empty($validatedData['amenities'])) {
            //      foreach ($validatedData['amenities'] as $amenity) {
            //          Amenity::create([
            //              'user_id' => $userId,
            //              'language_id' => $languageId,
            //              'name' => $amenity,
            //              'serial_number' => 0,
            //              'slug' => Str::slug($amenity),
            //              'icon' => 'fab fa-accusoft',
            //          ]);
            //      }
            //  }
     
             DB::commit();
     
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
                 'published' => (bool) $request->published,
                 'completion_date' => $request->completion_date,
                 'complete_status' => $request->complete_status,
                 'featured_image' => asset($project->featured_image),
                 'units' => $request->units,
                 'gallery_images' => $validatedData['gallery_images'] ?? [],
                 'floorplan_images' => $validatedData['floorplan_images'] ?? [],
                 'specifications' => $validatedData['specifications'] ?? [],
                 'contents' => $validatedData['contents'],
                 'types' => $validatedData['types'] ?? [],
                 'amenities' => $validatedData['amenities'] ?? [],
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
        //  } catch (\Exception $e) {
        //      DB::rollBack();
        //      Log::error('Project creation failed: ' . $e->getMessage());
        //      return response()->json([
        //          'status' => 'error',
        //          'message' => 'Project creation failed',
        //          'error' => $e->getMessage()
        //      ], 500);
        //  }
     }

    /**
     * Update an existing project.
     */
    public function update(Request $request, $id)
    {
        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['error' => 'Unauthorized: User ID is missing'], 401);
        }

        $project = Project::where('id', $id)
        ->where('user_id', $userId)
        ->firstOrFail();

        
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
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:255',
        ]);

        

        DB::beginTransaction();
        try {

            $project->update($validatedData);


            foreach ($validatedData['contents'] as $content) {
                ProjectContent::updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'language_id' => $content['language_id']
                    ],
                    array_merge($content, [
                        'project_id' => $project->id,
                        'user_id' => $userId,
                        'slug' => Str::slug($content['title']),
                    ])
                );
            }


            if (!empty($validatedData['types'])) {
                foreach ($validatedData['types'] as $type) {
                    ProjectType::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'language_id' => $type['language_id']
                        ],
                        array_merge($type, [
                            'project_id' => $project->id,
                            'user_id' => $userId,
                        ])
                    );
                }
            }
            $userLanguage = Language::where('user_id', $userId)->where('is_default', 1)->first();
            $languageId = $userLanguage ? $userLanguage->id : 1;


            DB::commit();

            $responseProject = [
                'id' => $project->id,
                'title' => $validatedData['contents'][0]['title'],
                'address' => $validatedData['contents'][0]['address'],
                'min_price' => $project->min_price,
                'max_price' => $project->max_price,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'featured' => (bool) $project->featured,
                'developer' => $project->developer,
                'published' => (bool) $project->published,
                'completion_date' => $project->completion_date,
                'featured_image' => asset($project->featured_image),
                'gallery' => !empty($validatedData['gallery_images']) ? collect($validatedData['gallery_images'])->map(function($image) {
                    return asset($image);
                })->toArray() : [],
                'floorplan_images' => !empty($validatedData['floorplan_images']) ? collect($validatedData['floorplan_images'])->map(function($image) {
                    return asset($image);
                })->toArray() : [],
                'specifications' => $validatedData['specifications'] ?? [],
                'contents' => $validatedData['contents'],
                'types' => $validatedData['types'] ?? [],
                'amenities' => $validatedData['amenities'] ?? [],
            ];

            return response()->json(['status' => 'success', 'message' => 'User Project updated successfully', 'data' => ['user_project' => $responseProject]], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Project update failed', 'error' => $e->getMessage()], 500);
        }
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
