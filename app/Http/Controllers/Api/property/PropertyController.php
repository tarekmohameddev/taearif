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
use App\Models\Api\ApiMenuItem;

class PropertyController extends Controller
{



    public function duplicate(Request $request, $propertyId)
    {
        $user = auth()->user();

        // Check if user has active membership
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

        // Check property limit
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

        // Find the original property with all relations
        $originalProperty = Property::where('id', $propertyId)
            ->where('user_id', $user->id) // Ensure user owns the property
            ->with([
                'contents',
                'galleryImages',
                'proertyAmenities',
                'UserPropertyCharacteristics',
                'specifications'
            ])
            ->first();

        if (!$originalProperty) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Property not found or you do not have permission to duplicate this property.',
            ], 404);
        }

        $defaultLanguage = Language::where('user_id', $user->id)
            ->where('is_default', 1)
            ->firstOrFail();

        // Validation rules for optional overrides
        $rules = [
            'title' => 'nullable|max:255',
            'address' => 'nullable',
            'description' => 'nullable',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'featured' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        $duplicatedProperty = null;

        DB::transaction(function () use ($request, $user, $defaultLanguage, $originalProperty, &$duplicatedProperty) {

            // Helper function to copy image files
            $copyImageFile = function ($originalPath) {
                if (empty($originalPath)) {
                    // \Log::warning("Empty image path given for copy.");
                    return $originalPath;
                }

                $sourcePath = public_path($originalPath);
                if (!file_exists($sourcePath)) {
                    // \Log::warning("Image not found in public/properties for copy: " . $originalPath);
                    return $originalPath;
                }

                $pathInfo = pathinfo($originalPath);
                $extension = $pathInfo['extension'] ?? '';
                $filename = $pathInfo['filename'] ?? '';
                $directory = $pathInfo['dirname'] ?? '';

                $newFilename = $filename . '_copy_' . time() . '_' . uniqid() . '.' . $extension;
                $newPath = $directory . '/' . $newFilename;

                $destination = public_path($newPath);

                // Ensure destination directory exists
                if (!is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0777, true);
                }

                if (copy($sourcePath, $destination)) {
                    return $newPath; // store in DB
                } else {
                    // \Log::warning("Failed to copy $sourcePath to $destination");
                    return $originalPath;
                }
            };



            // Copy main images
            $newFeaturedImage = $copyImageFile($originalProperty->featured_image);
            $newVideoImage = $copyImageFile($originalProperty->video_image);

            // Copy floor planning images
            $newFloorPlanningImages = null;
            if ($originalProperty->floor_planning_image) {
                $originalFloorPlans = $originalProperty->floor_planning_image; // $originalFloorPlans = json_decode($originalProperty->floor_planning_image, true); //
                if (is_array($originalFloorPlans)) {
                    $newFloorPlanningImages = [];
                    foreach ($originalFloorPlans as $floorPlan) {
                        $newFloorPlanningImages[] = $copyImageFile($floorPlan);
                    }
                }
            }

            // Prepare property data from original
            $propertyData = [
                'region_id' => $originalProperty->region_id,
                'price' => $request->price ?? $originalProperty->price,
                'pricePerMeter' => $request->pricePerMeter ?? $originalProperty->pricePerMeter,
                'purpose' => $originalProperty->purpose,
                'type' => $originalProperty->type,
                'beds' => $originalProperty->beds,
                'bath' => $originalProperty->bath,
                'area' => $originalProperty->area,
                'video_url' => $originalProperty->video_url,
                'virtual_tour' => $originalProperty->virtual_tour,
                'status' => $originalProperty->status,
                'latitude' => $originalProperty->latitude,
                'longitude' => $originalProperty->longitude,
                'features' => $originalProperty->features,
                'category_id' => $originalProperty->category_id,
                'project_id' => $originalProperty->project_id,
                'city_id' => $originalProperty->city_id,
                'state_id' => $originalProperty->state_id,
                'payment_method' => $originalProperty->payment_method,
                'facade_id' => $originalProperty->facade_id,
                'length' => $originalProperty->length,
                'width' => $originalProperty->width,
                'street_width_north' => $originalProperty->street_width_north,
                'street_width_south' => $originalProperty->street_width_south,
                'street_width_east' => $originalProperty->street_width_east,
                'street_width_west' => $originalProperty->street_width_west,
                'building_age' => $originalProperty->building_age,
                'rooms' => $originalProperty->rooms,
                'bathrooms' => $originalProperty->bathrooms,
                'floors' => $originalProperty->floors,
                'floor_number' => $originalProperty->floor_number,
                'driver_room' => $originalProperty->driver_room,
                'maid_room' => $originalProperty->maid_room,
                'dining_room' => $originalProperty->dining_room,
                'living_room' => $originalProperty->living_room,
                'majlis' => $originalProperty->majlis,
                'storage_room' => $originalProperty->storage_room,
                'basement' => $originalProperty->basement,
                'swimming_pool' => $originalProperty->swimming_pool,
                'kitchen' => $originalProperty->kitchen,
                'balcony' => $originalProperty->balcony,
                'garden' => $originalProperty->garden,
                'annex' => $originalProperty->annex,
                'elevator' => $originalProperty->elevator,
                'private_parking' => $originalProperty->private_parking,
            ];

            // Create the duplicated property with copied images
            $duplicatedProperty = Property::storeProperty(
                $user->id,
                $propertyData,
                $newFeaturedImage,
                $newFloorPlanningImages,
                $newVideoImage,
                $request->has('featured') ? $request->featured : $originalProperty->featured
            );

            // Duplicate property characteristics
            if ($originalProperty->UserPropertyCharacteristics) {
                $characteristics = $originalProperty->UserPropertyCharacteristics->toArray();
                unset($characteristics['id'], $characteristics['created_at'], $characteristics['updated_at']);
                $characteristics['property_id'] = $duplicatedProperty->id;
                UserPropertyCharacteristic::create($characteristics);
            }

            // Duplicate gallery images with file copying
            foreach ($originalProperty->galleryImages as $galleryImage) {
                $newGalleryImagePath = $copyImageFile($galleryImage->image);
                PropertySliderImg::storeSliderImage($user->id, $duplicatedProperty->id, $newGalleryImagePath);
            }

            // Duplicate amenities
            foreach ($originalProperty->proertyAmenities as $amenity) {
                PropertyAmenity::sotreAmenity($user->id, $duplicatedProperty->id, $amenity->amenity_id);
            }

            // Duplicate property content
            $originalContent = $originalProperty->contents->first();
            if ($originalContent) {
                $contentRequest = [
                    'language_id' => $defaultLanguage->id,
                    'category_id' => $originalContent->category_id,
                    'state_id' => $originalContent->state_id,
                    'city_id' => $originalContent->city_id,
                    'title' => $request->title ?? ($originalContent->title . ' (Copy)'),
                    'slug' => Str::slug($request->title ?? ($originalContent->title . ' Copy')),
                    'address' => $request->address ?? $originalContent->address,
                    'description' => $request->description ?? $originalContent->description,
                    'meta_keyword' => $originalContent->meta_keyword,
                    'meta_description' => $originalContent->meta_description,
                ];

                PropertyContent::storePropertyContent($user->id, $duplicatedProperty->id, $contentRequest);
            }

            // Duplicate specifications
            if ($originalProperty->specifications) {
                foreach ($originalProperty->specifications as $spec) {
                    $specData = [
                        'language_id' => $defaultLanguage->id,
                        'key' => $spec->key,
                        'label' => $spec->label,
                        'value' => $spec->value,
                    ];
                    PropertySpecification::storeSpecification($user->id, $duplicatedProperty->id, $specData);
                }
            }

            $this->ensureUnitsMenuItemExists($user->id);
        });

        // Load the duplicated property with relations
        $responseProperty = $duplicatedProperty->load([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'UserPropertyCharacteristics',
        ]);

        $content = $responseProperty->contents->first();

        $formattedProperty = [
            'id' => $responseProperty->id,
            'project_id' => $responseProperty->project_id,
            'payment_method' => $responseProperty->payment_method,
            'title' => optional($content)->title ?? 'No Title',
            'slug' => optional($content)->slug ?? 'No Slug',
            'address' => optional($content)->address ?? 'No Address',
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,
            'price' => $responseProperty->price,
            'pricePerMeter' => $responseProperty->pricePerMeter,
            'purpose' => $responseProperty->purpose,
            'type' => $responseProperty->type,
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => $responseProperty->area,
            'features' => $responseProperty->features,
            'characteristics' => $responseProperty->UserPropertyCharacteristics ?? null,
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
            'message' => 'Property duplicated successfully',
            'original_property_id' => $originalProperty->id,
            'duplicated_property' => $formattedProperty
        ], 201);
    }

    public function faqs(Request $request)
    {
        // $faqs = PropertyFaq::with('property')->get();
        Log::info('Fetching FAQs for properties');
        $faqs = [
            "suggestedFaqs" => [
                [
                    "question" => "متى يمكنني معاينة هذا العقار؟",
                    "priority" => 1
                ],
                [
                    "question" => "هل العقار مفروش؟",
                    "priority" => 2
                ],
                [
                    "question" => "ما هي سياسة الحيوانات الأليفة؟",
                    "priority" => 3
                ],
                [
                    "question" => "هل تتوفر مواقف للسيارات؟",
                    "priority" => 4
                ],
                [
                    "question" => "هل يوجد بواب أو حارس أمن؟",
                    "priority" => 5
                ]
            ]
        ];

        return response()->json([
            'status' => 'success',
            'data' => $faqs
        ]);

    }

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
                'payment_method' => $property->payment_method,
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
        ], 200);
    }

    public function show($id)
    {
        $property = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'UserPropertyCharacteristics',
        ])->findOrFail($id);

        $content = $property->contents->first();
        $characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];

        $formattedProperty = array_merge([
            'id' => $property->id,
            'project_id' => $property->project_id,
            'payment_method' => $property->payment_method,
            'title' => optional($content)->title ?? '',
            'address' => optional($content)->address ?? '',
            'price' => $property->price ?? '0.00',
            'pricePerMeter' => $property->pricePerMeter,
            'purpose' => $property->purpose,
            'type' => $property->type ?? '',
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'features' => $property->features ?? [],
            'status' => (int) $property->status,
            'featured_image' => asset($property->featured_image),
            'floor_planning_image' => collect($property->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
            'gallery' => $property->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? '',
            'latitude' => $property->latitude ? (float) $property->latitude : null,
            'longitude' => $property->longitude ? (float) $property->longitude : null,
            'featured' => (bool) $property->featured,
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,

            'category_id' => $property->category_id,
            'size' => $property->size ?? null,
            'faqs' => $property->faqs ?? [],
        ], $characteristics);

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
            'payment_method' => 'nullable',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'video_url' => 'nullable|string',
            'virtual_tour' => 'nullable|string',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'project_id' => 'nullable',
            'city_id' => 'nullable',
            'state_id' => 'nullable',
            'featured' => 'nullable|boolean',
            'amenities' => 'nullable|array',
            'type' => 'nullable',
            'faqs' => 'nullable|array',
            'category_id' => 'nullable|integer',
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
            'size' => 'nullable|integer',

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
                'pricePerMeter',
                'purpose',
                'type',
                'beds',
                'bath',
                'area',
                'video_url',
                'virtual_tour',
                'status',
                'latitude',
                'longitude',
                'features',
                // 'transaction_type',
                'category_id',
                'project_id',
                'city_id',
                'state_id',
                'payment_method',
                'faqs',

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
                "private_parking",
                'size',
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

            $this->ensureUnitsMenuItemExists($user->id); // Add properties menu item if not exists for the user
        });

        $responseProperty = $property->load([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'UserPropertyCharacteristics',
        ]);

        $content = $responseProperty->contents->first();

        $formattedProperty = [
            'id' => $responseProperty->id,
            'project_id' => $responseProperty->project_id,
            'payment_method' => $responseProperty->payment_method,
            'title' => optional($content)->title ?? 'No Title',
            'slug' => optional($content)->slug ?? 'No Slug',
            'address' => optional($content)->address ?? 'No Address',
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,
            'price' => $responseProperty->price,
            'pricePerMeter' => $responseProperty->pricePerMeter,
            'purpose' => $responseProperty->purpose,
            'type' => $responseProperty->type,
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => $responseProperty->area,
            'features' => $responseProperty->features,
            'characteristics' => $responseProperty->UserPropertyCharacteristics ?? null,
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
            'category_id' => $responseProperty->category_id,
            'faqs' => $responseProperty->faqs ?? [],
            'size' => $responseProperty->size ?? null,
            'floor_planning_image' => collect($responseProperty->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
            'video_image' => $responseProperty->video_image ? asset($responseProperty->video_image) : null,
            'virtual_tour' => $responseProperty->virtual_tour,
            'video_url' => $responseProperty->video_url,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'user_property' => $formattedProperty
        ], 201);
    }

    /**
     * Ensure the "properties" menu item exists for the user.
     * If it doesn't exist, create it.
     */
    private function ensureUnitsMenuItemExists($userId)
    {
        $exists = ApiMenuItem::where('user_id', $userId)
            ->where('url', '/properties')
            ->exists();

        if (!$exists) {
            $maxOrder = ApiMenuItem::where('user_id', $userId)->max('order') ?? 0;

            ApiMenuItem::create([
                'user_id' => $userId,
                'label' => 'الوحدات',
                'url' => '/properties',
                'is_external' => false,
                'is_active' => true,
                'order' => $maxOrder + 1,
                'parent_id' => null,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
            ]);
        }
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
            'payment_method' => 'nullable',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'project_id' => 'nullable',
            'city_id' => 'nullable',
            'state_id' => 'nullable',
            'amenities' => 'nullable|array',
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
            'size' => 'nullable|numeric',
            'type' => 'nullable',
            'faqs' => 'nullable|array',
            'video_url' => 'nullable|string',
            'virtual_tour' => 'nullable|string'
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
                // 'video_url',
                'status',
                'latitude',
                'longitude',
                'features',
                // 'transaction_type',
                // 'category_id',
                'project_id',
                'city_id',
                'state_id',
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
                "private_parking",
                'size',
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
        });

        $responseProperty = Property::with([
            'category',
            'user',
            'contents',
            'galleryImages',
            'proertyAmenities.amenity',
            'UserPropertyCharacteristics',

        ])->find($property->id);

        $content = $responseProperty->contents->first();
        $characteristics = optional($responseProperty->UserPropertyCharacteristics)->toArray() ?? [];

        $formattedProperty = array_merge([
            'payment_method' => $responseProperty->payment_method,
            'id' => $responseProperty->id,
            'video_url' => $responseProperty->video_url ? asset($responseProperty->video_url) : null,
            'virtual_tour' => $responseProperty->virtual_tour ? asset($responseProperty->virtual_tour) : null,
            'video_image' => $responseProperty->video_image ? asset($responseProperty->video_image) : null,
            'title' => optional($content)->title ?? '',
            'slug' => optional($content)->slug ?? '',
            'address' => optional($content)->address ?? '',
            'price' => $responseProperty->price ?? '0.00',
            'pricePerMeter' => $responseProperty->pricePerMeter,
            'purpose' => $responseProperty->purpose,
            'project_id' => $responseProperty->project_id ?? '',
            'type' => $responseProperty->type ?? '',
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => $responseProperty->area,
            'features' => $responseProperty->features ?? [],
            'status' => (int) $responseProperty->status,
            'featured_image' => asset($responseProperty->featured_image),
            'floor_planning_image' => collect($responseProperty->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
            'gallery' => $responseProperty->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? '',
            'latitude' => $responseProperty->latitude ? (float) $responseProperty->latitude : null,
            'longitude' => $responseProperty->longitude ? (float) $responseProperty->longitude : null,
            'featured' => (bool) $responseProperty->featured,
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,
            'category_id' => $responseProperty->category_id,
            'size' => $responseProperty->size ?? null,
            'faqs' => $responseProperty->faqs ?? [],
        ], $characteristics);

        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'property' => $formattedProperty,
        ], 200);
    }

    public function destroy($id)
    {
        $property = Property::with([
            'galleryImages',
            'proertyAmenities',
            'contents',
            'wishlists',
            // 'specifications'
        ])->findOrFail($id);

        $property->galleryImages()->delete();
        $property->proertyAmenities()->delete();
        $property->contents()->delete();
        $property->wishlists()->delete();
        // $property->specifications()->delete();

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

    public function toggleStatus($id)
    {
        $property = Property::findOrFail($id);

        $property->status = !$property->status;
        $property->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Property status updated successfully',
            'data' => ['status' => $property->status]
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
