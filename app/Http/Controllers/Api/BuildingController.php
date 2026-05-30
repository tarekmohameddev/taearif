<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\BuildingMeter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\Building\BuildingRequest;
use App\Http\Requests\Api\Building\UploadBuildingImageRequest as ApiUploadBuildingImageRequest;
use App\Http\Requests\Api\Building\UploadDeedImageRequest as ApiUploadDeedImageRequest;
use App\Http\Requests\Api\Building\UpdateBuildingRequest;

class BuildingController extends Controller
{
    /**
     * Display a listing of buildings.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        // Get Arabic language ID for property contents
        $arabicLang = \App\Models\User\Language::where('user_id', $user->id)
            ->where('code', 'ar')
            ->first();

        $languageId = $arabicLang ? $arabicLang->id : null;

        $query = Building::where('user_id', $owner->id)
            ->with([
                'user:id,username,email',
                'meters',
                'properties' => function($q) use ($languageId) {
                    $q->select('id', 'building_id', 'price', 'pricePerMeter', 'area', 'beds', 'bath', 'status', 'property_status', 'featured', 'featured_image', 'created_at')
                    ->with([
                        'contents' => function($q) use ($languageId) {
                            $q->select('id', 'property_id', 'language_id', 'title', 'slug', 'address', 'city_id', 'state_id', 'country_id');
                            if ($languageId) {
                                $q->where('language_id', $languageId);
                            }
                        },
                        'contents.district:id,name_ar,name_en,city_id,city_name_ar,city_name_en',
                        'contents.state:id,name',
                        'contents.country:id,name'
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
                    'city' => $content && $content->district ? $content->district->city_name_ar : 'N/A',
                    'state' => $content && $content->district
                        ? $content->district->name_ar
                        : ($content && $content->state ? $content->state->name : 'N/A'),
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
    public function store(BuildingRequest $request): JsonResponse
    {
        // Validation handled by FormRequest

        try {
            $user = Auth::user();
            $data = $request->only(['name', 'deed_number']);
            $data['user_id'] = $user->id;

            // Check if request is JSON (raw) or form-data for image handling logic
            $isJsonRequest = $request->isJson() || $request->header('Content-Type') === 'application/json';

            if ($isJsonRequest) {
                if ($request->has('image') && $request->image) {
                    $data['image'] = $request->image;
                }
                if ($request->has('deed_image') && $request->deed_image) {
                    $data['deed_image'] = $request->deed_image;
                }
            } else {
                if ($request->hasFile('image')) {
                    $data['image'] = $this->uploadImageFile($request->file('image'), 'buildings');
                }
                if ($request->hasFile('deed_image')) {
                    $data['deed_image'] = $this->uploadImageFile($request->file('deed_image'), 'buildings/deeds');
                }
            }

            $building = Building::create($data);
            $this->syncBuildingMeters($building, $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Building created successfully',
                'data' => $building->load(['user', 'meters'])
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
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        // Get Arabic language ID for property contents
        $arabicLang = \App\Models\User\Language::where('user_id', $user->id)
            ->where('code', 'ar')
            ->first();
        
        $languageId = $arabicLang ? $arabicLang->id : null;
        
        $building = Building::where('id', $id)
            ->where('user_id', $owner->id)
            ->with([
                'user:id,username,email',
                'meters',
                'properties' => function($q) use ($languageId) {
                    $q->select('id', 'building_id', 'price', 'pricePerMeter', 'area', 'beds', 'bath', 'status', 'property_status', 'featured', 'featured_image', 'created_at')
                    ->with([
                        'contents' => function($q) use ($languageId) {
                            $q->select('id', 'property_id', 'language_id', 'title', 'slug', 'address', 'city_id', 'state_id', 'country_id');
                            if ($languageId) {
                                $q->where('language_id', $languageId);
                            }
                        },
                        'contents.district:id,name_ar,name_en,city_id,city_name_ar,city_name_en',
                        'contents.state:id,name',
                        'contents.country:id,name'
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
                'city' => $content && $content->district ? $content->district->city_name_ar : 'N/A',
                'state' => $content && $content->district
                    ? $content->district->name_ar
                    : ($content && $content->state ? $content->state->name : 'N/A'),
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
    public function update(UpdateBuildingRequest $request, $id): JsonResponse
    {
        $user = Auth::user();
        $building = Building::where('id', $id)->where('user_id', $user->id)->first();

        if (!$building) {
            return response()->json([
                'status' => 'error',
                'message' => 'Building not found'
            ], 404);
        }

        try {
            $data = $request->only(['name', 'deed_number', 'image', 'deed_image']);
            $building->fill($data)->save();
            $this->syncBuildingMeters($building, $request);

            return response()->json([
                'status' => 'success',
                'message' => 'Building updated successfully',
                'data' => $building->load(['user', 'meters'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update building: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload building image (standalone endpoint).
     */
    public function uploadBuildingImage(ApiUploadBuildingImageRequest $request): JsonResponse
    {
        $path = $this->uploadImageFile($request->file('image'), 'buildings');
        return response()->json([
            'status' => 'success',
            'data' => ['image' => $path]
        ], 201);
    }

    /**
     * Upload deed image (standalone endpoint).
     */
    public function uploadDeedImage(ApiUploadDeedImageRequest $request): JsonResponse
    {
        $path = $this->uploadImageFile($request->file('deed_image'), 'buildings/deeds');
        return response()->json([
            'status' => 'success',
            'data' => ['deed_image' => $path]
        ], 201);
    }

    /**
     * Remove the specified building.
     */
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $building = Building::where('id', $id)->where('user_id', $user->id)->first();

        if (!$building) {
            return response()->json([
                'status' => 'error',
                'message' => 'Building not found'
            ], 404);
        }

        try {
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
     * Upload image file to public directory.
     */
    private function uploadImageFile($file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension();
        $fileName = 'building_' . time() . '_' . uniqid() . '.' . $extension;
        $fullDirectory = public_path($directory);

        if (!is_dir($fullDirectory)) {
            mkdir($fullDirectory, 0775, true);
        }

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

    /**
     * Sync building meters from request (water_meter_numbers and electricity_meter_numbers arrays).
     */
    private function syncBuildingMeters(Building $building, Request $request): void
    {
        $building->meters()->delete();

        $now = now();
        $meters = [];
        foreach ($request->input('water_meter_numbers', []) as $number) {
            $number = is_string($number) ? trim($number) : (string) $number;
            if ($number !== '') {
                $meters[] = [
                    'building_id' => $building->id,
                    'meter_type' => BuildingMeter::TYPE_WATER,
                    'meter_number' => $number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        foreach ($request->input('electricity_meter_numbers', []) as $number) {
            $number = is_string($number) ? trim($number) : (string) $number;
            if ($number !== '') {
                $meters[] = [
                    'building_id' => $building->id,
                    'meter_type' => BuildingMeter::TYPE_ELECTRICITY,
                    'meter_number' => $number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($meters)) {
            BuildingMeter::insert($meters);
        }
    }
}
