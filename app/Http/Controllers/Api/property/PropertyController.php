<?php

namespace App\Http\Controllers\Api\property;

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

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::with([
            'category',
            'user',
            'contents',
            'proertyAmenities.amenity'
        ])->paginate(10);

        $formattedProperties = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'title' => optional($property->contents->first())->title ?? 'No Title',
                'address' => optional($property->contents->first())->address ?? 'No Address',
                'price' => $property->price,
                'type' => $property->type,
                'bedrooms' => $property->beds,
                'bathrooms' => $property->bath,
                'size' => $property->area,
                'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
                'status' => $property->status,
                'featured_image' => asset('storage/properties/' . $property->featured_image),
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
            'bedrooms' => $property->beds,
            'bathrooms' => $property->bath,
            'size' => $property->area,
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




        Log::info($validatedData);

        $galleryImages = !empty($validatedData['gallery_images'])
            ? explode(',', $validatedData['gallery_images'])
            : [];


        $featuresArray = !empty($validatedData['features'])
            ? json_decode($validatedData['features'], true)
            : [];


        $property = Property::create([
            'user_id' => auth()->id(),
            'featured_image' => $validatedData['featured_image'],
            'price' => $validatedData['price'],
            'purpose' => $validatedData['status'],
            'type' => $validatedData['type'],
            'beds' => $validatedData['beds'] ?? null,
            'bath' => $validatedData['bath'] ?? null,
            'area' => $validatedData['area'],
            'status' => $validatedData['status'],
            'latitude' => $validatedData['latitude'],
            'longitude' => $validatedData['longitude'],
            'featured' => false,
            'gallery_images' => json_encode($galleryImages),
        ]);

        $propertyContent = PropertyContent::create([
            'user_id' => auth()->id(),
            'property_id' => $property->id,
            'city_id' => $validatedData['city_id'] ?? null,
            'country_id' => $validatedData['country_id'] ?? null,
            'state_id' => $validatedData['state_id'] ?? null,
            'language_id' => $validatedData['language_id'] ?? 1,
            'category_id' => $validatedData['category_id'] ?? 1,
            'title' => $validatedData['title'],
            'address' => $validatedData['address'],
            'description' => $validatedData['description'] ?? '',
        ]);


        if (!empty($featuresArray)) {
            foreach ($featuresArray as $amenityName) {
                PropertyAmenity::create([
                    'user_id' => auth()->id(),
                    'property_id' => $property->id,
                    /* 'amenity_name' => $amenityName,*/
                    'amenity_id' => $validatedData['amenity_id'],

                ]);
            }
        }

        $responseProperty = [
            'id' => $property->id,
            'title' => $propertyContent->title,
            'address' => $propertyContent->address,
            'price' => $property->price,
            'type' => $property->type,
            'bedrooms' => $property->beds,
            'bathrooms' => $property->bath,
            'size' => $property->area,
            /* 'features' => PropertyAmenity::where('property_id', $property->id)->pluck('amenity_name')->toArray(), */
            'features' => $featuresArray,
            'status' => $property->status,
            'featured_image' => $property->featured_image,
            'featured' => (bool) $property->featured,
            'gallery' => $galleryImages,
            'description' => $propertyContent->description,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'created_at' => $property->created_at->toISOString(),
            'updated_at' => $property->updated_at->toISOString(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'data' => [
                'property' => $responseProperty
            ]
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
        Log::info('Incoming Request Data:', $request->all());

        $statusMapping = [
            'For Sale' => 1,
            'Sold' => 2,
            'Rented' => 3,
            'Unavailable' => 0
        ];

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
                'gallery_images.*' => 'nullable|array',
                'features' => 'nullable|array',
                'description' => 'nullable|string',
                'featured' => 'nullable|boolean',
                'language_id' => 'nullable|integer',
                'city_id' => 'nullable|integer',
                'amenity_id' => 'nullable|array',
            ]);

            // Log::info('Validated Data:', $validatedData);

            // Start Transaction (Ensures everything updates together)
            DB::beginTransaction();

            // property with relations
            $property = Property::with('galleryImages', 'proertyAmenities', 'contents')->findOrFail($id);

            // Convert `status` to integer
            $validatedData['status'] = isset($statusMapping[$validatedData['status']])
                ? $statusMapping[$validatedData['status']]
                : (int) $validatedData['status'];

            // Check if the featured image is changing
            if (!empty($validatedData['featured_image']) && $validatedData['featured_image'] !== $property->featured_image) {
                Log::info("Updating Featured Image from {$property->featured_image} to {$validatedData['featured_image']}");
                $property->featured_image = $validatedData['featured_image'];
            }

            // Handle Gallery Images Update
            if (!empty($validatedData['gallery_images'])) {
                $galleryImages = explode(',', $validatedData['gallery_images']);

                // Log before deleting images
                Log::info("Deleting existing gallery images for property ID: {$property->id}");
                $property->galleryImages()->delete();

                // Insert new images
                foreach ($galleryImages as $imageUrl) {
                    Log::info("Inserting gallery image: {$imageUrl}");
                    PropertySliderImg::create([
                        'user_id' => Auth::id(),  // Ensure user is authenticated
                        'property_id' => $property->id,
                        'image' => $imageUrl
                    ]);
                }
            } else {
                $galleryImages = $property->galleryImages->pluck('image')->toArray();
            }

            // Log property before updating
            Log::info("Updating Property with ID: {$property->id}");

            // Update Property Fields
            $updateResult = $property->update([
                'price' => $validatedData['price'] ?? $property->price,
                'purpose' => $validatedData['status'] ?? $property->status,
                'type' => $validatedData['type'] ?? $property->type,
                'beds' => $validatedData['beds'] ?? $property->beds,
                'bath' => $validatedData['bath'] ?? $property->bath,
                'area' => $validatedData['area'] ?? $property->area,
                'status' => $validatedData['status'],
                'latitude' => $validatedData['latitude'] ?? $property->latitude,
                'longitude' => $validatedData['longitude'] ?? $property->longitude,
                'featured' => $request->has('featured') ? (bool) $request->featured : $property->featured,
            ]);

            Log::info("Property update result: " . ($updateResult ? 'Success' : 'Failed'));

            // Update Property Content
            $propertyContent = $property->contents->first();
            if ($propertyContent) {
                Log::info("Updating Property Content for ID: {$propertyContent->id}");
                $propertyContent->update([
                    'title' => $validatedData['title'] ?? $propertyContent->title,
                    'address' => $validatedData['address'] ?? $propertyContent->address,
                    'description' => $validatedData['description'] ?? $propertyContent->description,
                ]);
            }

            // Update Features (Amenities)
            if (!empty($validatedData['features'])) {
                Log::info("Updating Amenities for Property ID: {$property->id}");
                $property->proertyAmenities()->delete();
                foreach ($validatedData['features'] as $amenityId) {
                    PropertyAmenity::create([
                        'user_id' => Auth::id(),
                        'property_id' => $property->id,
                        'amenity_id' => $amenityId
                    ]);
                }
            }

            // Commit the transaction
            DB::commit();
            Log::info("Transaction Committed Successfully for Property ID: {$property->id}");

            // Format Response Data
            $responseProperty = [
                'id' => $property->id,
                'title' => $propertyContent->title,
                'address' => $propertyContent->address,
                'price' => $property->price,
                'type' => $property->type,
                'bedrooms' => $property->beds,
                'bathrooms' => $property->bath,
                'size' => $property->area,
                'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
                'status' => $property->status,
                'featured_image' => $property->featured_image,
                'featured' => (bool) $property->featured,
                'gallery' => $galleryImages,
                'description' => $propertyContent->description,
                'location' => [
                    'latitude' => $property->latitude,
                    'longitude' => $property->longitude,
                ],
                'created_at' => $property->created_at->toISOString(),
                'updated_at' => $property->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Property updated successfully',
                'data' => [
                    'property' => $responseProperty
                ]
            ]);

        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            Log::error("Error updating property: " . $e->getMessage(), ['trace' => $e->getTrace()]);
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
