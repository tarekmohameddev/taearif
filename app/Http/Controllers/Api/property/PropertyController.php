<?php

namespace App\Http\Controllers\Api\property;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertyContact;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertyWishlist;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\Amenity;
use Illuminate\Support\Facades\Validator;


class PropertyController extends Controller
{
    public function index(Request $request)
    {

        $user = $request->user();
        $properties = Property::with([
            'category',
            'user',
            'contents',
            'proertyAmenities.amenity'
        ])->where('user_id', $user->id)->paginate(10);
        
            log::info(json_encode($properties));
        $formattedProperties = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'title' => optional($property->contents->first())->title ?? 'No Title',
                'address' => optional($property->contents->first())->address ?? 'No Address',
                'price' => $property->price,
                'type' => $property->type,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => $property->area,
                'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
                'status' => $property->status,
                'featured_image' => $property->featured_image,
                'featured' => (bool) $property->featured,
                'created_at' => $property->created_at->toISOString(),
                'updated_at' => $property->updated_at->toISOString(),
            ];
        });


        return response()->json([
            'status' => 'success',
            'data' => [
                'properties' => $formattedProperties,
                'pagination' => [
                    'total' => $properties->total(),
                    'per_page' => $properties->perPage(),
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'from' => $properties->firstItem(),
                    'to' => $properties->lastItem(),
                ]
            ]
        ]);


    }

    public function show($id)
    {
        $property = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity'
        ])->findOrFail($id);

        $formattedProperty = [
            'id' => $property->id,
            'title' => optional($property->contents->first())->title ?? 'No Title',
            'address' => optional($property->contents->first())->address ?? 'No Address',
            'price' => $property->price,
            'type' => $property->type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
            'status' => $property->status,
            'featured_image' => asset('storage/properties/' . $property->featured_image),
            'featured' => (bool) $property->featured,
            'gallery' => $property->galleryImages->pluck('image')->map(fn($image) => asset('storage/properties/' . $image))->toArray(),
            'description' => optional($property->contents->first())->description ?? 'No Description',
            'location' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'created_at' => $property->created_at->toISOString(),
            'updated_at' => $property->updated_at->toISOString(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'property' => $formattedProperty
            ]
        ]);

    }

    /*
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    * @throws \Illuminate\Validation\ValidationException
    * @throws \Exception
    * @throws \Throwable
    * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
    */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric',
            'type' => 'sometimes|required|string',
            'beds' => 'nullable|integer',
            'bath' => 'nullable|numeric',
            'area' => 'sometimes|required|numeric',
            'status' => 'sometimes|required',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'featured_image' => 'nullable|string',
            'floor_planning_image' => 'nullable|array',
            'floor_planning_image.*' => 'nullable|string',
            'features' => 'nullable|array',
            'description' => 'nullable|string',
            'featured' => 'nullable|boolean',
            'language_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'category_id' => 'nullable|integer',
            'amenity_id' => 'nullable|array',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $validatedData = $validator->validated();
        $floorPlanningImages = $validatedData['floor_planning_image'] ?? [];
    
        $property = Property::create([
            'user_id' => $user->id,
            'featured_image' => $validatedData['featured_image'] ?? null,
            'price' => $validatedData['price'],
            'purpose' => $validatedData['status'],
            'type' => $validatedData['type'],
            'beds' => $validatedData['beds'] ?? null,
            'bath' => $validatedData['bath'] ?? null,
            'area' => $validatedData['area'],
            'status' => $validatedData['status'],
            'latitude' => $validatedData['latitude'] ?? null,
            'longitude' => $validatedData['longitude'] ?? null,
            'featured' => false,
        ]);
    
        $propertyContent = PropertyContent::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'city_id' => $validatedData['city_id'] ?? null,
            'category_id' => $validatedData['category_id'] ?? null,
            'language_id' => $validatedData['language_id'] ?? 1,
            'title' => $validatedData['title'],
            'slug' => make_slug($validatedData['title'] ?? Str::random(8)),
            'address' => $validatedData['address'],
            'description' => $validatedData['description'] ?? '',
        ]);
    
        if (!empty($floorPlanningImages)) {
            $property->update([
                'floor_planning_image' => json_encode($floorPlanningImages)
            ]);
        }
    
        $featuresArray = $validatedData['features'] ?? [];
    
        if (!empty($validatedData['features'])) {
            foreach ($validatedData['features'] as $amenity) {
                $Amenity = Amenity::create([
                    'user_id' => $user->id,
                    'language_id' => 1,
                    'name' => $amenity,
                    'serial_number' => 0,
                    'slug' => Str::slug($amenity),
                    'icon' => 'fab fa-accusoft',
                ]);
    
                PropertyAmenity::create([
                    'user_id' => $user->id,
                    'property_id' => $property->id,
                    'amenity_id' => $Amenity->id,
                ]);
            }
        }
    
        $responseProperty = [
            'id' => $property->id,
            'title' => $propertyContent->title,
            'address' => $propertyContent->address,
            'price' => $property->price,
            'type' => $property->type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'features' => $featuresArray,
            'status' => $property->status,
            'featured_image' => $property->featured_image,
            'featured' => (bool) $property->featured,
            'description' => $propertyContent->description,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'created_at' => $property->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $property->updated_at->format('Y-m-d H:i:s'),
        ];
    
        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'data' => ['property' => $responseProperty],
        ], 201);
    }



    /*
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\JsonResponse
    * @throws \Illuminate\Validation\ValidationException
    * @throws \Exception
    * @throws \Throwable
    */

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $validatedData = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'address' => 'sometimes|required|string',
                'price' => 'sometimes|required|numeric',
                'type' => 'sometimes|required|string',
                'beds' => 'nullable|integer',
                'bath' => 'nullable|numeric',
                'area' => 'sometimes|required|numeric',
                'status' => 'sometimes|required',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'featured_image' => 'nullable|string',
                'gallery_images' => 'nullable|string',
                'floor_planning_image' => 'nullable|array',
                'floor_planning_image.*' => 'nullable|string',
                'features' => 'nullable|array',
                'description' => 'nullable|string',
                'featured' => 'nullable|boolean',
                'language_id' => 'nullable|integer',
                'category_id' => 'nullable|integer',
                'city_id' => 'nullable|integer',
                'amenity_id' => 'nullable|array',
            ]);

            DB::beginTransaction();

            $property = Property::with(['galleryImages', 'proertyAmenities.amenity', 'contents'])->findOrFail($id);

            if (!empty($validatedData['featured_image']) && $validatedData['featured_image'] !== $property->featured_image) {
                $property->featured_image = $validatedData['featured_image'];
            }

            $galleryImages = [];
            if (!empty($validatedData['gallery_images'])) {
                $galleryImages = explode(',', $validatedData['gallery_images']);
                $property->galleryImages()->delete();

                foreach ($galleryImages as $imageUrl) {
                    PropertySliderImg::create([
                        'user_id' => $user->id,
                        'property_id' => $property->id,
                        'image' => trim($imageUrl)
                    ]);
                }
            }

            if (!empty($validatedData['floor_planning_image'])) {
                $property->update([
                    'floor_planning_image' => json_encode($validatedData['floor_planning_image'])
                ]);
            }

            $property->update([
                'price' => $validatedData['price'] ?? $property->price,
                'status' => $validatedData['status'],
                'type' => $validatedData['type'] ?? $property->type,
                'beds' => $validatedData['beds'] ?? $property->beds,
                'bath' => $validatedData['bath'] ?? $property->bath,
                'area' => $validatedData['area'] ?? $property->area,
                'latitude' => $validatedData['latitude'] ?? $property->latitude,
                'longitude' => $validatedData['longitude'] ?? $property->longitude,
                'featured' => $request->has('featured') ? (bool) $request->featured : $property->featured,
            ]);

            $propertyContent = $property->contents->first();
            if (!$propertyContent) {
                $propertyContent = PropertyContent::create([
                    'property_id' => $property->id,
                    'user_id' => $user->id,
                    'title' => $validatedData['title'] ?? 'Untitled Property',
                    'slug' => make_slug($validatedData['title'] ?? Str::random(8)),
                    'address' => $validatedData['address'] ?? 'No Address',
                    'description' => $validatedData['description'] ?? '',
                    'city_id' => $validatedData['city_id'] ?? null,
                    'category_id' => $validatedData['category_id'] ?? null,
                    'language_id' => $validatedData['language_id'] ?? 1,
                ]);
            } else {
                $propertyContent->update([
                    'title' => $validatedData['title'] ?? $propertyContent->title,
                    'address' => $validatedData['address'] ?? $propertyContent->address,
                    'description' => $validatedData['description'] ?? $propertyContent->description,
                    'city_id' => $validatedData['city_id'] ?? $propertyContent->city_id,
                    'category_id' => $validatedData['category_id'] ?? $propertyContent->category_id,
                    'language_id' => $validatedData['language_id'] ?? $propertyContent->language_id,
                ]);
            }

            if (!empty($validatedData['amenity_id']) && is_array($validatedData['amenity_id'])) {
                $property->proertyAmenities()->delete();

                foreach ($validatedData['amenity_id'] as $amenityId) {
                    PropertyAmenity::create([
                        'user_id' => $user->id,
                        'property_id' => $property->id,
                        'amenity_id' => $amenityId
                    ]);
                }
            }

            DB::commit();

            $property->load(['galleryImages', 'proertyAmenities.amenity', 'contents']);

            $responseProperty = [
                'id' => $property->id,
                'title' => $propertyContent->title,
                'address' => $propertyContent->address,
                'price' => $property->price,
                'type' => $property->type,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => $property->area,
                'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
                'status' => $property->status,
                'featured_image' => $property->featured_image,
                'featured' => (bool) $property->featured,
                'gallery' => $property->galleryImages->pluck('image')->toArray(),
                'floor_planning_image' => json_decode($property->floor_planning_image),
                'description' => $propertyContent->description,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'created_at' => $property->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $property->updated_at->format('Y-m-d H:i:s'),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Property updated successfully',
                'data' => ['property' => $responseProperty],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Property update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function destroy($id)
    {
        $property = Property::with([
            'galleryImages',
            'proertyAmenities',
            'contents',
            'wishlists',
            'specifications'
        ])->findOrFail($id);

        $property->galleryImages()->delete();
        $property->proertyAmenities()->delete();
        $property->contents()->delete();
        $property->wishlists()->delete();
        $property->specifications()->delete();

        if ($property->featured_image) {
            Storage::delete('public/properties/' . $property->featured_image);
        }

        $property->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Property deleted successfully'
        ], 200);

    }

    public function toggleFeatured($id)
    {
        $property = Property::findOrFail($id);

        $property->featured = !$property->featured;
        $property->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Property featured status updated',
            'data' => ['featured' => $property->featured]
        ]);
    }

    public function toggleFavorite($id)
    {
        $userId = Auth::id();
        $customer = Auth::user()->customers()->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found for this user. Please create a customer first.'
            ], 400);
        }

        $customerId = $customer->id;
        $wishlist = PropertyWishlist::where('user_id', $userId)
            ->where('property_id', $id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $isFavorite = false;
        } else {
            PropertyWishlist::create([
                'user_id' => $userId,
                'property_id' => $id,
                'customer_id' => $customerId,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Property favorite status updated',
            'data' => ['is_favorite' => $isFavorite]
        ]);
    }

    private function getGalleryImages($property)
    {
        if ($property && isset($property->gallery_images)) {
            return array_map(fn($img) => "/storage/properties/" . $img, json_decode($property->gallery_images, true));
        }


        return [
            "/storage/properties/default-1.jpg",
            "/storage/properties/default-2.jpg",
            "/storage/properties/default-3.jpg"
        ];
    }


}
