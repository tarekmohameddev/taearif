<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Get Arabic language ID for property contents
        $arabicLang = \App\Models\User\Language::where('user_id', $user->id)
            ->where('code', 'ar')
            ->first();
        
        $languageId = $arabicLang ? $arabicLang->id : null;

        $query = Building::where('user_id', $user->id)
            ->with([
                'user',
                'properties' => function($q) use ($languageId) {
                    $q->with([
                        'contents' => function($q) use ($languageId) {
                            if ($languageId) {
                                $q->where('language_id', $languageId);
                            }
                        },
                        'contents.city',
                        'contents.state',
                        'contents.country'
                    ]);
                }
            ])
            ->orderBy('created_at', 'desc');

        // Search by name
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $buildings = $query->paginate($request->get('per_page', 15));

        // Transform the properties data to include only needed fields
        $buildings->getCollection()->transform(function ($building) {
            $building->properties->transform(function ($property) {
                $content = $property->contents->first();
                
                return [
                    'id' => $property->id,
                    'title' => $content->title ?? 'N/A',
                    'slug' => $content->slug ?? null,
                    'address' => $content->address ?? 'N/A',
                    'price' => $property->price,
                    'pricePerMeter' => $property->pricePerMeter,
                    'area' => $property->area,
                    'beds' => $property->beds,
                    'bath' => $property->bath,
                    'status' => $property->status,
                    'property_status' => $property->property_status,
                    'featured' => (bool)$property->featured,
                    'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
                    'city' => $content && $content->city ? $content->city->name : 'N/A',
                    'state' => $content && $content->state ? $content->state->name : 'N/A',
                    'country' => $content && $content->country ? $content->country->name : 'N/A',
                    'created_at' => $property->created_at->toISOString(),
                ];
            });
            
            return $building;
        });

        return response()->json([
            'status' => 'success',
            'data' => $buildings
        ]);
    }

    /**
     * Store a newly created building.
     */
    public function store(Request $request): JsonResponse
    {
        // Check if request is JSON (raw) or form-data
        $isJsonRequest = $request->isJson() || $request->header('Content-Type') === 'application/json';
        
        if ($isJsonRequest) {
            // Handle JSON request with file paths
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|string|max:500',
                'deed_number' => 'nullable|string|max:255',
                'deed_image' => 'nullable|string|max:500',
                'water_meter_number' => 'nullable|string|max:255',
            ]);
        } else {
            // Handle form-data request with file uploads
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'image' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'deed_number' => 'nullable|string|max:255',
                'deed_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
                'water_meter_number' => 'nullable|string|max:255',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $data = $request->only(['name', 'deed_number', 'water_meter_number']);
            $data['user_id'] = $user->id;

            if ($isJsonRequest) {
                // Handle JSON request - use provided file paths directly
                if ($request->has('image') && $request->image) {
                    $data['image'] = $request->image;
                }
                if ($request->has('deed_image') && $request->deed_image) {
                    $data['deed_image'] = $request->deed_image;
                }
            } else {
                // Handle file uploads
                if ($request->hasFile('image')) {
                    $data['image'] = $this->uploadImageFile($request->file('image'), 'buildings');
                }
                if ($request->hasFile('deed_image')) {
                    $data['deed_image'] = $this->uploadImageFile($request->file('deed_image'), 'buildings/deeds');
                }
            }

            $building = Building::create($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Building created successfully',
                'data' => $building->load('user')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified building.
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        // Get Arabic language ID for property contents
        $arabicLang = \App\Models\User\Language::where('user_id', $user->id)
            ->where('code', 'ar')
            ->first();
        
        $languageId = $arabicLang ? $arabicLang->id : null;
        
        $building = Building::where('id', $id)
            ->where('user_id', $user->id)
            ->with([
                'user',
                'properties' => function($q) use ($languageId) {
                    $q->with([
                        'contents' => function($q) use ($languageId) {
                            if ($languageId) {
                                $q->where('language_id', $languageId);
                            }
                        },
                        'contents.city',
                        'contents.state',
                        'contents.country'
                    ]);
                }
            ])
            ->first();

        if (!$building) {
            return response()->json([
                'status' => 'error',
                'message' => 'Building not found'
            ], 404);
        }

        // Transform the properties data
        $building->properties->transform(function ($property) {
            $content = $property->contents->first();

            return [
                'id' => $property->id,
                'title' => $content->title ?? 'N/A',
                'slug' => $content->slug ?? null,
                'address' => $content->address ?? 'N/A',
                'price' => $property->price,
                'pricePerMeter' => $property->pricePerMeter,
                'area' => $property->area,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'status' => $property->status,
                'property_status' => $property->property_status,
                'featured' => (bool)$property->featured,
                'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
                'city' => $content && $content->city ? $content->city->name : 'N/A',
                'state' => $content && $content->state ? $content->state->name : 'N/A',
                'country' => $content && $content->country ? $content->country->name : 'N/A',
                'created_at' => $property->created_at->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $building
        ]);
    }

    /**
     * Update the specified building.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        
        $building = Building::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$building) {
            return response()->json([
                'status' => 'error',
                'message' => 'Building not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'image' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'deed_number' => 'nullable|string|max:255',
            'deed_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'water_meter_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only(['name', 'deed_number', 'water_meter_number']);

            // Handle building image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($building->image) {
                    $this->deleteImage($building->image);
                }
                $data['image'] = $this->uploadImageFile($request->file('image'), 'buildings');
            }

            // Handle deed image upload
            if ($request->hasFile('deed_image')) {
                // Delete old deed image if exists
                if ($building->deed_image) {
                    $this->deleteImage($building->deed_image);
                }
                $data['deed_image'] = $this->uploadImageFile($request->file('deed_image'), 'buildings/deeds');
            }

            $building->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Building updated successfully',
                'data' => $building->load('user')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified building.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        
        $building = Building::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$building) {
            return response()->json([
                'status' => 'error',
                'message' => 'Building not found'
            ], 404);
        }

        // Check if building has properties linked
        if (!$building->canBeDeleted()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete building. It has properties linked to it.'
            ], 422);
        }

        try {
            // Delete images if they exist
            if ($building->image) {
                $this->deleteImage($building->image);
            }
            if ($building->deed_image) {
                $this->deleteImage($building->deed_image);
            }

            $building->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Building deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload building image.
     */
    public function uploadBuildingImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('image');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'building_' . time() . '_' . uniqid() . '.' . $extension;
            
            $directory = public_path('buildings');
            
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            
            // Move file to directory
            $file->move($directory, $fileName);
            
            // Return the relative path
            $filePath = 'buildings/' . $fileName;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded successfully',
                'data' => [
                    'path' => $filePath,
                    'url' => asset($filePath),
                    'filename' => $fileName
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload building deed image.
     */
    public function uploadDeedImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deed_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('deed_image');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'deed_' . time() . '_' . uniqid() . '.' . $extension;
            
            $directory = public_path('buildings/deeds');
            
            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            
            // Move file to directory
            $file->move($directory, $fileName);
            
            // Return the relative path
            $filePath = 'buildings/deeds/' . $fileName;
            
            return response()->json([
                'status' => 'success',
                'message' => 'Deed image uploaded successfully',
                'data' => [
                    'path' => $filePath,
                    'url' => asset($filePath),
                    'filename' => $fileName
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload deed image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload image helper method.
     */
    private function uploadImageFile($file, $directory): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = 'building_' . time() . '_' . uniqid() . '.' . $extension;
        
        $fullDirectory = public_path($directory);
        
        // Create directory if it doesn't exist
        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0775, true);
        }
        
        // Move file to directory
        $file->move($fullDirectory, $fileName);
        
        return $directory . '/' . $fileName;
    }

    /**
     * Delete image helper method.
     */
    private function deleteImage($imagePath): void
    {
        if ($imagePath && file_exists(public_path($imagePath))) {
            unlink(public_path($imagePath));
        }
    }
}
