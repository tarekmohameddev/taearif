<?php

namespace App\Http\Controllers\Api\property;

use App\Models\Membership;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\BasicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\PropertyCharacteristic;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertyContact;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertyWishlist;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertySpecification;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User\RealestateManagement\ApiUserCategory as Category;

class PropertyController extends Controller
{



    public function properties_categories(Request $request)
    {
        $categories = ApiUserCategory::where('is_active', true)
            ->where('type', 'property')
            ->get(['id', 'name']);
        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }



    public function index(Request $request)
    {

        $user = $request->user();
        $properties = Property::with([
            'category',
            'user',
            'contents',
            'proertyAmenities.amenity'
        ])->where('user_id', $user->id)->paginate(10);


        $formattedProperties = $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'title' => optional($property->contents->first())->title ?? 'No Title',
                'address' => optional($property->contents->first())->address ?? 'No Address',
                'slug' => optional($property->contents->first())->slug,
                'price' => $property->price,
                'type' => $property->type,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => $property->area,
                'transaction_type' => $property->purpose,
                'features' => $property->features,
                'status' => $property->status,
                'featured_image' => asset($property->featured_image),
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
                ],200);


    }

    public function show($id)
    {
        $property = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'specifications',
            'UserPropertyCharacteristics',
        ])->findOrFail($id);

        $formattedProperty = [
            'id' => $property->id,
            'project_id' => $property->project_id,
            'title' => optional($property->contents->first())->title ?? 'No Title',
            'address' => optional($property->contents->first())->address ?? 'No Address',
            'price' => $property->price,
            'type' => $property->type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'features' => $property->proertyAmenities->pluck('amenity.name')->toArray(),
            'characteristics' => $property->UserPropertyCharacteristics  ?? null,
            'specifications'    => $property->specifications->map(function ($spec) {
                return [
                    'key' => $spec->key,
                    'label' => $spec->label,
                    'value' => $spec->value,
                ];
            })->toArray(),
            'status' => $property->status,
            'featured_image' => asset($property->featured_image),
            'featured' => (bool) $property->featured,
            'gallery' => $property->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
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

        $membership = Membership::where('user_id', $user->id)
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

        $realEstateLimit = $membership->package->real_estate_limit_number;
        $currentPropertyCount = Property::where('user_id', $user->id)->count();

        if (!is_null($realEstateLimit) && $currentPropertyCount >= $realEstateLimit) {
            return response()->json([
                'status' => false,
                'message' => 'You have reached your property listing limit.',
                'limit' => $realEstateLimit,
                'used' => $currentPropertyCount
            ], 403);
        }

        $defaultLanguage = Language::where('user_id', $user->id)
            ->where('is_default', 1)
            ->firstOrFail();

        $rules = [
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'project_id' => 'nullable',
            'city_id' => 'nullable',
            'featured' => 'nullable',
            'amenities' => 'nullable|array',
            'label' => 'nullable|array',
            'value' => 'nullable|array',
            'category_id' => 'nullable|integer',

            // Property Characteristics
            'facade_id' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'street_width_north' => 'nullable|numeric',
            'street_width_south' => 'nullable|numeric',
            'street_width_east' => 'nullable|numeric',
            'street_width_west' => 'nullable|numeric',
            'building_age' => 'nullable|integer',
            'rooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'floors' => 'nullable|integer',
            'floor_number' => 'nullable|integer',
            'driver_room' => 'nullable|integer',
            'maid_room' => 'nullable|integer',
            'dining_room' => 'nullable|integer',
            'living_room' => 'nullable|integer',
            'majlis' => 'nullable|integer',
            'storage_room' => 'nullable|integer',
            'basement' => 'nullable|integer',
            'swimming_pool' => 'nullable|integer',
            'kitchen' => 'nullable|integer',
            'balcony' => 'nullable|integer',
            'garden' => 'nullable|integer',
            'annex' => 'nullable|integer',
            'elevator' => 'nullable|integer',
            'private_parking' => 'nullable|integer',

        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = null;

        DB::transaction(function () use ($request, $user, $defaultLanguage, &$property) {
            $featuredImgName = $request->featured_image;
            $videoImage = $request->video_image;
            $featured = $request->featured;
            $floorPlanningImage = $request->floor_planning_image;

            if (!empty($floorPlanningImage)) {
                if (is_string($floorPlanningImage)) {
                    $floorPlanningImage = [$floorPlanningImage];
                }
            } else {
                $floorPlanningImage = null;
            }

            $propertyData = $request->only([
                'region_id',
                'price',
                'purpose',
                'type',
                'beds',
                'bath',
                'area',
                'video_url',
                'status',
                'latitude',
                'longitude',
                'features',
                'transaction_type',
                'category_id',
                'project_id',

                "facade_id",
                "length",
                "width",
                "street_width_north",
                "street_width_south",
                "street_width_east",
                "street_width_west",
                "building_age",
                "rooms",
                "bathrooms",
                "floors",
                "floor_number",
                "driver_room",
                "maid_room",
                "dining_room",
                "living_room",
                "majlis",
                "storage_room",
                "basement",
                "swimming_pool",
                "kitchen",
                "balcony",
                "garden",
                "annex",
                "elevator",
                "private_parking"
            ]);

            $property = Property::storeProperty(
                $user->id,
                $propertyData,
                $featuredImgName,
                $floorPlanningImage,
                $videoImage,
                $featured
            );

            $characteristics = $request->only([
                'facade_id',
                'length',
                'width',
                'street_width_north',
                'street_width_south',
                'street_width_east',
                'street_width_west',
                "building_age",
                "rooms",
                "bathrooms",
                "floors",
                "floor_number",
                "driver_room",
                "maid_room",
                "dining_room",
                "living_room",
                "majlis",
                "storage_room",
                "basement",
                "swimming_pool",
                "kitchen",
                "balcony",
                "garden",
                "annex",
                "elevator",
                "private_parking"
            ]);

            $characteristics['property_id'] = $property->id;
            $characteristics['facade_id'] = !empty($characteristics['facade_id']) ? $characteristics['facade_id'] : null;

            UserPropertyCharacteristic::create($characteristics);


            if ($request->has('gallery')) {
                foreach ($request->gallery as $imagePath) {
                    PropertySliderImg::storeSliderImage($user->id, $property->id, $imagePath);
                }
            }

            if ($request->has('amenities')) {
                foreach ((array) $request->amenities as $amenity) {
                    PropertyAmenity::sotreAmenity($user->id, $property->id, $amenity);
                }
            }

            $contentRequest = [
                'language_id' => $defaultLanguage->id,
                'category_id' => $request->category_id ?? ApiUserCategory::where('slug', 'other')->value('id'),
                'state_id' => $request->state_id ?? 3,
                'city_id' => $request->city_id,
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'address' => $request->address,
                'description' => $request->description,
                'meta_keyword' => $request->meta_keyword ?? null,
                'meta_description' => $request->meta_description ?? null,
            ];

            PropertyContent::storePropertyContent($user->id, $property->id, $contentRequest);

            $labels = (array) $request->input('label', []);
            $values = (array) $request->input('value', []);

            foreach ($labels as $key => $label) {
                if (!empty($values[$key])) {
                    $spec = [
                        'language_id' => $defaultLanguage->id,
                        'key' => $key,
                        'label' => $label,
                        'value' => $values[$key],
                    ];
                    PropertySpecification::storeSpecification($user->id, $property->id, $spec);
                }
            }
        });

        $responseProperty = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'specifications',
            'UserPropertyCharacteristics'
        ])->findOrFail($property->id);

        $content = $responseProperty->contents->first();

        $formattedProperty = [
            'id' => $responseProperty->id,
            'project_id' => $responseProperty->project_id,
            'title' => optional($content)->title ?? 'No Title',
            'address' => optional($content)->address ?? 'No Address',
            'price' => $responseProperty->price,
            'type' => $responseProperty->type,
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => $responseProperty->area,
            'features' => $responseProperty->proertyAmenities->pluck('amenity.name')->toArray(),
            'characteristics' => $responseProperty->UserPropertyCharacteristics ?? null,
            'specifications' => $responseProperty->specifications->map(function ($spec) {
                return [
                    'key' => $spec->key,
                    'label' => $spec->label,
                    'value' => $spec->value,
                ];
            })->toArray(),
            'status' => (bool) $responseProperty->status,
            'featured' => (bool) $responseProperty->featured,
            'featured_image' => asset($responseProperty->featured_image),
            'gallery' => $responseProperty->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? 'No Description',
            'location' => [
                'latitude' => $responseProperty->latitude,
                'longitude' => $responseProperty->longitude,
            ],
            'created_at' => $responseProperty->created_at->toISOString(),
            'updated_at' => $responseProperty->updated_at->toISOString(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'user_property' => $formattedProperty
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

        $property = Property::where('user_id', $user->id)->findOrFail($id);

        $defaultLanguage = Language::where('user_id', $user->id)
            ->where('is_default', 1)
            ->firstOrFail();

        $rules = [
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'project_id' => 'nullable',
            'city_id' => 'nullable',
            'amenities' => 'nullable|array',
            'label' => 'nullable|array',
            'value' => 'nullable|array',
            'category_id' => 'nullable|integer',
            // Property Characteristics
            'facade_id' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'street_width_north' => 'nullable|numeric',
            'street_width_south' => 'nullable|numeric',
            'street_width_east' => 'nullable|numeric',
            'street_width_west' => 'nullable|numeric',
            'building_age' => 'nullable|integer',
            'rooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'floors' => 'nullable|integer',
            'floor_number' => 'nullable|integer',
            'driver_room' => 'nullable|integer',
            'maid_room' => 'nullable|integer',
            'dining_room' => 'nullable|integer',
            'living_room' => 'nullable|integer',
            'majlis' => 'nullable|integer',
            'storage_room' => 'nullable|integer',
            'basement' => 'nullable|integer',
            'swimming_pool' => 'nullable|integer',
            'kitchen' => 'nullable|integer',
            'balcony' => 'nullable|integer',
            'garden' => 'nullable|integer',
            'annex' => 'nullable|integer',
            'elevator' => 'nullable|integer',
            'private_parking' => 'nullable|integer',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $user, $defaultLanguage, &$property) {
            $property->updateProperty($request->all());

            $characteristics = $request->only([
                'region_id',
                'price',
                'purpose',
                'type',
                'beds',
                'bath',
                'area',
                'video_url',
                'status',
                'latitude',
                'longitude',
                'features',
                'transaction_type',
                'category_id',
                'project_id',

                "facade_id",
                "length",
                "width",
                "street_width_north",
                "street_width_south",
                "street_width_east",
                "street_width_west",
                "building_age",
                "rooms",
                "bathrooms",
                "floors",
                "floor_number",
                "driver_room",
                "maid_room",
                "dining_room",
                "living_room",
                "majlis",
                "storage_room",
                "basement",
                "swimming_pool",
                "kitchen",
                "balcony",
                "garden",
                "annex",
                "elevator",
                "private_parking"
            ]);
            $characteristics['facade_id'] = !empty($characteristics['facade_id']) ? $characteristics['facade_id'] : null;

            UserPropertyCharacteristic::updateOrCreate(
                ['property_id' => $property->id],
                $characteristics
            );


            if ($request->has('gallery')) {
                PropertySliderImg::where('property_id', $property->id)->delete();

                foreach ($request->gallery as $imagePath) {
                    PropertySliderImg::storeSliderImage($user->id, $property->id, $imagePath);
                }
            }

            PropertyAmenity::where('property_id', $property->id)->delete();
            PropertyContent::where('property_id', $property->id)->delete();
            PropertySpecification::where('property_id', $property->id)->delete();

            if ($request->has('amenities')) {
                foreach ((array) $request->amenities as $amenity) {
                    PropertyAmenity::sotreAmenity($user->id, $property->id, $amenity);
                }
            }

            $contentRequest = [
                'language_id' => $defaultLanguage->id,
                'category_id' => $request->category_id ?? ApiUserCategory::where('slug', 'other')->value('id'),
                'state_id' => $request->state_id ?? 3,
                'city_id' => $request->city_id,
                'title' => $request->title,
                'slug' => Str::slug($request->title),
                'address' => $request->address,
                'description' => $request->description,
                'meta_keyword' => $request->meta_keyword ?? null,
                'meta_description' => $request->meta_description ?? null,
            ];

            PropertyContent::storePropertyContent($user->id, $property->id, $contentRequest);

            $labels = (array) $request->input('label', []);
            $values = (array) $request->input('value', []);

            foreach ($labels as $key => $label) {
                if (!empty($values[$key])) {
                    $spec = [
                        'language_id' => $defaultLanguage->id,
                        'key' => $key,
                        'label' => $label,
                        'value' => $values[$key],
                    ];
                    PropertySpecification::storeSpecification($user->id, $property->id, $spec);
                }
            }
        });

        $responseProperty = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'specifications',
            'UserPropertyCharacteristics'
        ])->find($property->id);

        $content = $responseProperty->contents->first();

        $formattedProperty = [
            'id' => $responseProperty->id,
            'project_id' => $responseProperty->project_id,
            'title' => optional($content)->title ?? 'No Title',
            'address' => optional($content)->address ?? 'No Address',
            'price' => $responseProperty->price,
            'type' => $responseProperty->type,
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => $responseProperty->area,
            'features' => $responseProperty->proertyAmenities->pluck('amenity.name')->toArray(),
            'characteristics' => $responseProperty->UserPropertyCharacteristics ?? null,
            'specifications' => $responseProperty->specifications->map(function ($spec) {
                return [
                    'key' => $spec->key,
                    'label' => $spec->label,
                    'value' => $spec->value,
                ];
            })->toArray(),
            'status' => (bool) $responseProperty->status,
            'featured' => (bool) $responseProperty->featured,
            'featured_image' => asset($responseProperty->featured_image),
            'gallery' => $responseProperty->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? 'No Description',
            'location' => [
                'latitude' => $responseProperty->latitude,
                'longitude' => $responseProperty->longitude,
            ],
            'created_at' => $responseProperty->created_at->toISOString(),
            'updated_at' => $responseProperty->updated_at->toISOString(),
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'property' => $formattedProperty
        ], 200);
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
