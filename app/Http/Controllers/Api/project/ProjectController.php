<?php

namespace App\Http\Controllers\Api\project;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
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

         // Get user's projects with related data
         $projects = Project::with(['contents', 'specifications', 'types'])
             ->where('user_id', $user->id)
             ->paginate(10);

         // If user has no projects, create a default one
         if ($projects->isEmpty()) {
             $defaultProject = Project::create([
                 'user_id' => $user->id,
                 'featured_image' => null,
                 'min_price' => 0,
                 'max_price' => 0,
                 'latitude' => 0,
                 'longitude' => 0,
                 'featured' => false,
                 'complete_status' => 'In Progress',
                 'units' => 0,
                 'completion_date' => now()->addYear()->toDateString(),
                 'developer' => 'Default Developer',
                 'published' => false,
             ]);

             // Create default content
             ProjectContent::create([
                 'user_id' => $user->id,
                 'project_id' => $defaultProject->id,
                 'title' => 'Default Project Title',
                 'address' => 'Default Address',
                 'description' => 'This is a default project.',
                 'meta_keyword' => 'default, project',
                 'meta_description' => 'Default project description.',
                 'slug' => Str::slug('Default Project Title')
             ]);

             // Reload with fresh pagination
             $projects = Project::with(['contents', 'specifications', 'types'])
                 ->where('user_id', $user->id)
                 ->paginate(10);
         }

         // Format the project data
         $formattedProjects = $projects->getCollection()->map(function ($project) {
             return [
                 "id" => $project->id,
                 "featured_image" => $project->featured_image ? asset($project->featured_image) : null,
                 "price_range" => number_format($project->min_price, 2),
                 "latitude" => $project->latitude,
                 "longitude" => $project->longitude,
                 "featured" => (bool) $project->featured,
                 "complete_status" => $project->complete_status,
                 "units" => $project->units,
                 "completion_date" => $project->completion_date,
                 "developer" => $project->developer,
                 "published" => (bool) $project->published,
                 "created_at" => $project->created_at->toISOString(),
                 "updated_at" => $project->updated_at->toISOString(),
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
        $userId = auth()->id();
        $defaultLang = Language::where('user_id', $userId)->where('is_default', 1)->firstOrFail();

        $rules = [
            'gallery_images' => 'required|array',
            'gallery_images.*' => 'string',

            'floor_plan_images' => 'required|array',
            'floor_plan_images.*' => 'string',

            'featured_image' => 'required|string',
            'min_price' => 'required|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'sometimes',
            'status' => 'required',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],

            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required|min:15',
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

        DB::transaction(function () use ($request, $userId, $defaultLang, &$project) {
            $requestData = $request->all();
            $requestData['featured_image'] = $request->featured_image;

            $project = Project::storeProject($userId, $requestData);

            foreach ($request->gallery_images as $imgPath) {
                ProjectGalleryImg::storeGalleryImage($userId, $project->id, $imgPath);
            }

            foreach ($request->floor_plan_images as $imgPath) {
                ProjectFloorplanImg::storeFloorplanImage($userId, $project->id, $imgPath);
            }

            $content = [
                'project_id' => $project->id,
                'language_id' => $defaultLang->id,
                'title' => $request->title,
                'address' => $request->address,
                'description' => $request->description,
                'meta_keyword' => $request->meta_keyword ?? null,
                'meta_description' => $request->meta_description ?? null,
            ];
            ProjectContent::storeProjectContent($userId, $content);

            $labels = $request->input('label', []);
            $values = $request->input('value', []);

            foreach ($labels as $key => $label) {
                if (!empty($values[$key])) {
                    ProjectSpecification::storeSpecification($userId, [
                        'language_id' => $defaultLang->id,
                        'project_id' => $project->id,
                        'key' => $key,
                        'label' => $label,
                        'value' => $values[$key],
                    ]);
                }
            }
        });

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications'
        ])->find($project->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => [
                'project' => $responseProject
            ]
        ], 201);
    }


    /**
     * Update an existing project.
    */

    public function update(Request $request, $id)
    {
        $userId = auth()->id();
        $defaultLang = Language::where('user_id', $userId)->where('is_default', 1)->firstOrFail();

        $project = Project::where('user_id', $userId)->findOrFail($id);

        $rules = [
            'gallery_images' => 'sometimes|array',
            'gallery_images.*' => 'string',
            'floor_plan_images' => 'sometimes|array',
            'floor_plan_images.*' => 'string',
            'featured_image' => 'required|string',
            'min_price' => 'required|numeric',
            'max_price' => 'nullable|numeric',
            'featured' => 'sometimes',
            'status' => 'required',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required|min:15',
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

        DB::transaction(function () use ($request, $userId, $defaultLang, &$project) {
            $requestData = $request->all();
            $requestData['featured_image'] = $request->featured_image;
            $project->updateProject($requestData);
            if ($request->has('gallery_images')) {
                ProjectGalleryImg::where('project_id', $project->id)->delete();
                foreach ($request->gallery_images as $imgPath) {
                    ProjectGalleryImg::storeGalleryImage($userId, $project->id, $imgPath);
                }
            }
            if ($request->has('floor_plan_images')) {
                ProjectFloorplanImg::where('project_id', $project->id)->delete();
                foreach ($request->floor_plan_images as $imgPath) {
                    ProjectFloorplanImg::storeFloorplanImage($userId, $project->id, $imgPath);
                }
            }
            ProjectContent::where('project_id', $project->id)->delete();
            $content = [
                'project_id' => $project->id,
                'language_id' => $defaultLang->id,
                'title' => $request->title,
                'address' => $request->address,
                'description' => $request->description,
                'meta_keyword' => $request->meta_keyword ?? null,
                'meta_description' => $request->meta_description ?? null,
            ];
            ProjectContent::storeProjectContent($userId, $content);
            ProjectSpecification::where('project_id', $project->id)->delete();

            $labels = $request->input('label', []);
            $values = $request->input('value', []);

            foreach ($labels as $key => $label) {
                if (!empty($values[$key])) {
                    ProjectSpecification::storeSpecification($userId, [
                        'language_id' => $defaultLang->id,
                        'project_id' => $project->id,
                        'key' => $key,
                        'label' => $label,
                        'value' => $values[$key],
                    ]);
                }
            }
        });

        $responseProject = Project::with([
            'galleryImages',
            'floorPlanImages',
            'contents',
            'specifications'
        ])->find($project->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => [
                'project' => $responseProject
            ]
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
