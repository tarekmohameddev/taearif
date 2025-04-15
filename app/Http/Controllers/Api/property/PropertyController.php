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
use App\Models\User\RealestateManagement\ApiUserCategory as Category;

class PropertyController extends Controller
{

    // public function properties_categories(Request $request){
    //     $user = Auth::user();
    //     $properties_categories = [
    //         ["type" => "residential", "name" => "شقة"],
    //         ["type" => "residential", "name" => "دور"],
    //         ["type" => "residential", "name" => "فيلا"],
    //         ["type" => "residential", "name" => "دوبلكس"],
    //         ["type" => "residential", "name" => "قصر"],
    //         ["type" => "residential", "name" => "مبنى سكني"],
    //         ["type" => "residential", "name" => "برج سكني"],
    //         ["type" => "residential", "name" => "استراحة"],
    //         ["type" => "residential", "name" => "مزرعة"],
    //         ["type" => "commercial", "name" => "ارض"],
    //         ["type" => "commercial", "name" => "تاون هاوس"],
    //         ["type" => "commercial", "name" => "مبنى"],
    //         ["type" => "commercial", "name" => "صالة عرض"],
    //         ["type" => "commercial", "name" => "منتجع"],
    //         ["type" => "commercial", "name" => "مكاتب"],
    //         ["type" => "commercial", "name" => "تجاري"],

    //     ];
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'categories' => $properties_categories
    //         ]
    //     ]);

    // }

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
            'gallery' => 'required|array',
            'gallery.*' => 'string',
            'featured_image' => 'required|string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['required', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['required', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'category_id' => 'required',
            'city_id' => 'required',
            'featured' => 'nullable',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required|min:15',
            'amenities' => 'nullable|array',
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
                'transaction_type'
            ]);

            $property = Property::storeProperty(
                $user->id,
                $propertyData,
                $featuredImgName,
                $floorPlanningImage,
                $videoImage,
                $featured
            );

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
                'category_id' => $request->category_id,
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
            'sliderImages',
            'amenities',
            'contents',
            'specifications'
        ])->find($property->id);


        if ($responseProperty->purpose) {
            $responseProperty->transaction_type = $responseProperty->purpose;
        }

        if ($responseProperty->featured_image) {
            $responseProperty->featured_image = asset($responseProperty->featured_image);
        }

        if (is_array($responseProperty->floor_planning_image)) {
            $responseProperty->floor_planning_image = array_map(function($image) {
                return asset($image);
            }, $responseProperty->floor_planning_image);
        }


        $responseProperty->gallery = $responseProperty->sliderImages->pluck('image')
            ->map(function($image) {
                return asset($image);
            })->toArray();


       unset($responseProperty->sliderImages);
       unset($responseProperty->amenities);
       unset($responseProperty->purpose);

        $responseProperty->featured = (bool) $responseProperty->featured;
        $responseProperty->status = (bool) $responseProperty->status;

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'user_property' => $responseProperty
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
            'gallery' => 'sometimes|array',
            'gallery.*' => 'string',
            'featured_image' => 'required|string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['required', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['required', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'category_id' => 'required',
            'city_id' => 'required',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required|min:15',
            'amenities' => 'nullable|array',
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

        DB::transaction(function () use ($request, $user, $defaultLanguage, &$property) {
            $property->updateProperty($request->all());

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
                'category_id' => $request->category_id,
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
            'sliderImages',
            'amenities',
            'contents',
            'specifications'
        ])->find($property->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'user_property' => $responseProperty
        ]);
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
