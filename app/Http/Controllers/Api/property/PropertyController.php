<?php

namespace App\Http\Controllers\Api\property;

use App\Models\Membership;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\Api\ApiMenuItem;
use App\Support\TenantActivity;
use App\Models\User\BasicSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
use App\Support\Audit;
use App\Services\GoogleAnalyticsService;
use Carbon\Carbon;
use App\Services\AlibabaOssService;
use App\Services\MembershipCacheService;


use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PropertiesImport;

class PropertyController extends Controller
{
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $user = auth()->user();

        // Check if user has active membership (cached)
        $membership = MembershipCacheService::getActiveMembership($user->id);

        if (!$membership || !$membership->package) {
            return response()->json([
                'status' => 'fail',
                'message' => 'No active package found for the user.',
            ], 403);
        }

        // Check property limit before processing the file
        $realEstateLimit = $membership->package->real_estate_limit_number;
        $currentPropertyCount = Property::where('user_id', $user->id)->count();

        // Estimate row count from uploaded file
        try {
            // Smart Method: Calculate the exact number of rows with data
            // This avoids reading thousands of empty rows
            $filePath = $request->file('file')->getPathname();
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true); // Optimization: Read only data, ignore formatting
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow(); // This gets the last row with actual data
            
            // Pass the smart limit to the import class
            // highestRow includes header, so we pass it as the limit
            $import = new PropertiesImport($user->id, $highestRow);
            
            $collection = Excel::toCollection($import, $request->file('file'));
            
            // Count only actual data rows (excluding header which is row 1)
            // The collection excludes the header due to WithHeadingRow trait
            $firstSheet = $collection->first();
            // Filter out empty rows (all values are null or empty)
            $incomingRowCount = $firstSheet->filter(function($row) {
                $rowArray = $row->toArray();
                
                // Skip rows marked as empty by prepareForValidation
                if (isset($rowArray['_skip_empty_row']) && $rowArray['_skip_empty_row'] === true) {
                    return false;
                }
                
                // Skip rows where title is the dummy empty marker
                if (isset($rowArray['title']) && $rowArray['title'] === '_EMPTY_ROW_SKIP_') {
                    return false;
                }
                
                // Skip completely empty rows (all values are null or empty)
                $hasData = !empty(array_filter($rowArray, function($value) {
                    return !is_null($value) && $value !== '';
                }));
                
                return $hasData;
            })->count();
            
            if (!is_null($realEstateLimit) && ($currentPropertyCount + $incomingRowCount) > $realEstateLimit) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Bulk import would exceed your property listing limit.',
                    'limit' => $realEstateLimit,
                    'current_count' => $currentPropertyCount,
                    'incoming_count' => $incomingRowCount,
                    'available_slots' => max(0, $realEstateLimit - $currentPropertyCount)
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to read uploaded file: ' . $e->getMessage(),
            ], 422);
        }

        try {
        // Use the same smart limit for the actual import
        $import = new PropertiesImport($user->id, $highestRow);
        Excel::import($import, $request->file('file'));

        $failures = $import->sheetImport->failures();
        $errors = $import->sheetImport->errors();
        $detailedErrors = [];

        // Collect Validation Failures
        foreach ($failures as $failure) {
            $detailedErrors[] = [
                'row' => $failure->row(),
                'message' => 'Validation Error: ' . implode(', ', $failure->errors()),
                'values' => $failure->values(),
            ];
        }

        // Collect Logic Errors (Exceptions)
        foreach ($errors as $error) {
            $message = $error->getMessage();
            $row = null;
            
            // Extract row number if present in exception message
            if (preg_match('/Row (\d+):/', $message, $matches)) {
                $row = (int)$matches[1];
            }

            $detailedErrors[] = [
                'row' => $row,
                'message' => $message,
            ];
        }

        $importedCount = $import->sheetImport->importedCount;
        $failedCount = count($detailedErrors);

        if ($failedCount > 0) {
            return response()->json([
                'status' => 'partial_success', // or 'fail' if 0 imported
                'message' => "Import completed with issues. Imported: {$importedCount}, Failed: {$failedCount}.",
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
                'errors' => $detailedErrors,
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Import successful. {$importedCount} properties created.",
            'imported_count' => $importedCount,
            'failed_count' => 0,
            'errors' => [],
        ]);

    } catch (\Exception $e) {
        Log::error('Bulk property import critical error', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'A critical error occurred during import: ' . $e->getMessage(),
        ], 500);
    }
    }

    public function downloadTemplate()
    {
        return Excel::download(new \App\Exports\PropertiesTemplateExport, 'properties_import_template.xlsx');
    }



    private $videoService;

    public function __construct(AlibabaOssService $ossService)
    {
        $this->videoService = $ossService;
    }

    public function duplicate(Request $request, $propertyId)
    {
        $user = auth()->user();

        // Check if user has active membership (cached)
        $membership = MembershipCacheService::getActiveMembership($user->id);

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
                    return $originalPath;
                }

                $sourcePath = public_path($originalPath);
                if (!file_exists($sourcePath)) {
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
                $request->has('featured') ? $request->featured : $originalProperty->featured,
                auth()->id()
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
                    'slug' => str_replace('.', '', Str::slug($request->title ?? ($originalContent->title . ' Copy'))),
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
            'show_reservations' => (bool) $responseProperty->show_reservations,
            'featured_image' => asset($responseProperty->featured_image),
            'gallery' => $responseProperty->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? 'No Description',
            'location' => [
                'latitude' => $responseProperty->latitude,
                'longitude' => $responseProperty->longitude,
            ],
            'created_at' => $responseProperty->created_at->toISOString(),
            'updated_at' => $responseProperty->updated_at->toISOString(),
            'building' => $responseProperty->building,
            'water_meter_number' => $responseProperty->water_meter_number,
            'electricity_meter_number' => $responseProperty->electricity_meter_number,
            'deed_number' => $responseProperty->deed_number ? asset($responseProperty->deed_number) : null,
        ];

        TenantActivity::emit($request, 'property.duplicated', 'user_properties', $duplicatedProperty->id, [
            'source_property_id' => $originalProperty->id
        ], [
            'duplicated_property_id' => $duplicatedProperty->id
        ]);

        Audit::property($user->id, $duplicatedProperty->id, 'custom', "duplicated from {$originalProperty->id}");

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

    //properties_reorder_featured

    public function properties_reorder_featured(Request $request)
    {
        $user = $request->user();
        $payload = $request->all();
        if (isset($payload[0])) {
            $payload = $payload[0];
        }

        $propertyId = $payload['id'] ?? null;
        $newPosition = (int) ($payload['reorder_featured'] ?? 0);

        if (!$propertyId || $newPosition <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Invalid input'], 400);
        }

        $properties = Property::where('user_id', $user->id)
            ->where('featured', 1) // only reorder featured ones
            ->orderByRaw('COALESCE(reorder_featured, 999999) ASC') // nulls last
            ->get();


        $movingProperty = $properties->firstWhere('id', $propertyId);
        if (!$movingProperty) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found or not featured for this user'
            ], 404);
        }

        $properties = $properties->filter(fn($p) => $p->id !== $propertyId);

        $newPosition = max(1, min($newPosition, $properties->count() + 1));

        $reordered = $properties->values()->toArray();

        array_splice($reordered, $newPosition - 1, 0, [$movingProperty]);

        DB::transaction(function () use ($reordered) {
            foreach ($reordered as $index => $prop) {
                Property::where('id', $prop['id'])->update(['reorder_featured' => $index + 1]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Featured property reordered successfully'
        ]);
    }

    // properties_reorder

    public function properties_reorder(Request $request)
    {
        $user = $request->user();
        $payload = $request->all();
        if (isset($payload[0])) {
            $payload = $payload[0];
        }

        $propertyId = $payload['id'] ?? null;
        $newPosition = (int) ($payload['reorder'] ?? 0);

        if (!$propertyId || $newPosition <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Invalid input'], 400);
        }

        $properties = Property::where('user_id', $user->id)
            ->orderByRaw('COALESCE(reorder, 999999) ASC') // handle nulls
            ->get();

        $movingProperty = $properties->firstWhere('id', $propertyId);

        if (!$movingProperty) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found for this user'
            ], 404);
        }

        $properties = $properties->filter(fn($p) => $p->id !== $propertyId);
        $newPosition = max(1, min($newPosition, $properties->count() + 1));
        $reordered = $properties->values()->toArray();

        array_splice($reordered, $newPosition - 1, 0, [$movingProperty]);

        DB::transaction(function () use ($reordered) {
            foreach ($reordered as $index => $prop) {
                Property::where('id', $prop['id'])->update(['reorder' => $index + 1]);
            }
        });

        Audit::property($user->id, (int)$propertyId, 'custom', "reordered featured list to position {$newPosition}");

        return response()->json([
            'status' => 'success',
            'message' => 'Property reordered successfully'
        ]);
    }


    public function show($id, GoogleAnalyticsService $analytics)
    {
        try {
            $property = Property::with([
                'category',
                'user',
                'contents',
                'galleryImages',
                'proertyAmenities.amenity',
                'UserPropertyCharacteristics',
                'creator',
            ])->findOrFail($id);

            $content = $property->contents->first();
            $characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];

            // Fetch views from Google Analytics (CACHED - 5 minutes)
            $views = 0;
            if ($content && $content->slug && $property->user) {
                $days = (int) request()->query('days', 30);
                $tenantId = $property->user->username;
                $cacheKey = "ga_views_property_{$id}_{$tenantId}_{$days}_{$content->slug}";
                
                $views = Cache::remember($cacheKey, 300, function () use ($analytics, $days, $content, $tenantId) {
                    $result = 0;
                    try {
                        // Build paths for this property (with and without language prefixes)
                        $paths = [
                            "/property/{$content->slug}",
                            "/ar/property/{$content->slug}",
                            "/en/property/{$content->slug}",
                        ];

                        $allData = $analytics->getAllAnalyticsWithFilters(
                            now()->subDays($days),
                            now(),
                            [
                                'tenant_ids' => [$tenantId],
                                'exclude_empty_tenant' => false,
                                'limit' => count($paths) * 10,
                            ]
                        );

                        // Sum views across all path variants
                        foreach ($allData['data'] as $item) {
                            if (in_array($item['path'], $paths)) {
                                $result += (int) $item['views'];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error('Google Analytics error in admin PropertyController show', [
                            'property_id' => $property->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return $result;
                });
            }

            $formattedProperty = array_merge([
                'id' => $property->id,
                'project_id' => $property->project_id,
                'payment_method' => $property->payment_method,
                'title' => optional($content)->title ?? '',
                'address' => optional($content)->address ?? '',
                'price' => isset($property->price) ? formatNumberWithoutTrailingZeros($property->price) : '0',
                'views' => $views,
                'pricePerMeter' => isset($property->pricePerMeter) ? formatNumberWithoutTrailingZeros($property->pricePerMeter) : null,
                'purpose' => $property->purpose,
                'type' => $property->type ?? '',
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : null,
                'features' => $property->features ?? [],
                'status' => (int) $property->status,
                'featured_image' => asset($property->featured_image),
                'floor_planning_image' => collect($property->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
                'gallery' => $property->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
                'description' => optional($content)->description ?? '',
                'latitude' => $property->latitude ? (float) $property->latitude : null,
                'longitude' => $property->longitude ? (float) $property->longitude : null,
                'featured' => (bool) $property->featured,
                'show_reservations' => (bool) $property->show_reservations,
                'city_id' => optional($content)->city_id,
                'state_id' => optional($content)->state_id,
                'video_url' => $property->video_url ? asset($property->video_url) : null,
                'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
                'video_image' => $property->video_image ? asset($property->video_image) : null,
                'category_id' => $property->category_id,
                'size' => $property->size ?? null,
                'faqs' => $property->faqs ?? [],
                'building' => $property->building,
                'water_meter_number' => $property->water_meter_number,
                'electricity_meter_number' => $property->electricity_meter_number,
                'deed_number' => $property->deed_number ? asset($property->deed_number) : null,
                'creator' => $property->creator ? [
                    'id'   => $property->creator->id,
                    'name' => trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? '')) ?: ($property->creator->username ?? $property->creator->email),
                    'type' => $property->creator->account_type,
                ] : null,
            ], $characteristics);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'property' => $formattedProperty
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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

        // Resolve tenant owner (tenant for tenant; tenant for employee)
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        // Check if user has active membership (cached)
        $membership = MembershipCacheService::getActiveMembership($owner->id);

        if (!$membership || !$membership->package) {
            return response()->json([
                'status' => 'fail',
                'message' => 'No active package found for the user.',
            ], 403);
        }

        $realEstateLimit = $membership->package->real_estate_limit_number;
        $currentPropertyCount = Property::where('user_id', $owner->id)->count();

        if (!is_null($realEstateLimit) && $currentPropertyCount >= $realEstateLimit) {
            return response()->json([
                'status' => false,
                'message' => 'You have reached your property listing limit.',
                'limit' => $realEstateLimit,
                'used' => $currentPropertyCount
            ], 403);
        }

        $defaultLanguage = Language::where('user_id', $owner->id)
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
            'floor_planning_image' => ['nullable'],
            'video_image' => 'nullable|string',
            'video_url' => 'nullable|string',// For direct URL or OSS URL
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
            'building_id' => 'nullable|integer|exists:buildings,id',
            'water_meter_number' => 'nullable|string',
            'electricity_meter_number' => 'nullable|string',
            'deed_number' => 'nullable|string',
            'video_file' => 'nullable|file', // Video upload now handled separately via VideoUploadController
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
                'building_id',
                'water_meter_number',
                'electricity_meter_number',
                'deed_number',
                'show_reservations',

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

            // Normalize features to array format
            if (isset($propertyData['features'])) {
                if (is_string($propertyData['features'])) {
                    // Try to decode as JSON first
                    $decoded = json_decode($propertyData['features'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $propertyData['features'] = $decoded;
                    } else {
                        // If it's a plain string, wrap it in an array
                        $propertyData['features'] = [$propertyData['features']];
                    }
                } elseif (!is_array($propertyData['features'])) {
                    // If it's neither string nor array, default to empty array
                    $propertyData['features'] = [];
                }
            } else {
                $propertyData['features'] = [];
            }

            $videoUrl = $request->video_url; // Video URL from separate upload

            $property = Property::storeProperty(
                $user->id,
                $propertyData,
                $featuredImgName,
                $floorPlanningImage,
                $videoImage,
                $featured,
                auth()->id()
            );

            // Update the property with video URL if provided
            if ($videoUrl) {
                $property->update(['video_url' => $videoUrl]);
            }

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
                'slug' => str_replace('.', '', Str::slug($request->title)),
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
            'show_reservations' => (bool) $responseProperty->show_reservations,
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
            'building' => $responseProperty->building,
            'water_meter_number' => $responseProperty->water_meter_number,
            'electricity_meter_number' => $responseProperty->electricity_meter_number,
            'deed_number' => $responseProperty->deed_number ? asset($responseProperty->deed_number) : null,
        ];

        TenantActivity::emit($request, 'property.created', 'user_properties', $responseProperty->id, null, [
            'id' => $responseProperty->id, 'title' => $formattedProperty['title'] ?? null
        ]);

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

        // Resolve tenant owner (tenant for tenant; tenant for employee)
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

        // Permit updating properties owned by the tenant or any of their employees
        $allowedUserIds = [$owner->id];
        try {
            $employeeIds = \App\Models\User::where('tenant_id', $owner->id)->pluck('id')->toArray();
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {
            // ignore and fall back to owner-only scoping
        }

        $property = Property::whereIn('user_id', $allowedUserIds)->where('id', $id)->first();
        if (!$property) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found for this tenant',
            ], 404);
        }

        $defaultLanguage = Language::where('user_id', $owner->id)
            ->where('is_default', 1)
            ->first();
        if (!$defaultLanguage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Default language not configured for this tenant',
            ], 404);
        }

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
            'building_id' => 'nullable|integer|exists:buildings,id',
            'water_meter_number' => 'nullable|string',
            'electricity_meter_number' => 'nullable|string',
            'deed_number' => 'nullable|string',
            'video_url' => 'nullable|string',// For direct URL or OSS URL
            'virtual_tour' => 'nullable|string',
            'video_file' => 'nullable|file', // Video upload now handled separately via VideoUploadController
            'show_reservations' => 'nullable|boolean',

        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'fail',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($request, $user, $defaultLanguage, &$property) {

            $videoUrl = $request->video_url; // Video URL from separate upload

            // Update property data with video URL
            $requestData = $request->all();
            if ($videoUrl) {
                $requestData['video_url'] = $videoUrl;
            }

            // Normalize features to array format
            if (isset($requestData['features'])) {
                if (is_string($requestData['features'])) {
                    // Try to decode as JSON first
                    $decoded = json_decode($requestData['features'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $requestData['features'] = $decoded;
                    } else {
                        // If it's a plain string, wrap it in an array
                        $requestData['features'] = [$requestData['features']];
                    }
                } elseif (!is_array($requestData['features'])) {
                    // If it's neither string nor array, default to empty array
                    $requestData['features'] = [];
                }
            } else {
                // If features is not provided, don't override existing value (handled in updateProperty)
            }

            $property->updateProperty($requestData);

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
                'slug' => str_replace('.', '', Str::slug($request->title)),
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
            'price' => isset($responseProperty->price) ? formatNumberWithoutTrailingZeros($responseProperty->price) : '0',
            'pricePerMeter' => isset($responseProperty->pricePerMeter) ? formatNumberWithoutTrailingZeros($responseProperty->pricePerMeter) : null,
            'purpose' => $responseProperty->purpose,
            'project_id' => $responseProperty->project_id ?? '',
            'type' => $responseProperty->type ?? '',
            'beds' => $responseProperty->beds,
            'bath' => $responseProperty->bath,
            'area' => isset($responseProperty->area) ? formatNumberWithoutTrailingZeros($responseProperty->area) : null,
            'features' => $responseProperty->features ?? [],
            'status' => (int) $responseProperty->status,
            'featured_image' => asset($responseProperty->featured_image),
            'floor_planning_image' => collect($responseProperty->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
            'gallery' => $responseProperty->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
            'description' => optional($content)->description ?? '',
            'latitude' => $responseProperty->latitude ? (float) $responseProperty->latitude : null,
            'longitude' => $responseProperty->longitude ? (float) $responseProperty->longitude : null,
            'featured' => (bool) $responseProperty->featured,
            'show_reservations' => (bool) $responseProperty->show_reservations,
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,
            'category_id' => $responseProperty->category_id,
            'size' => $responseProperty->size ?? null,
            'faqs' => $responseProperty->faqs ?? [],
            'building' => $responseProperty->building,
            'water_meter_number' => $responseProperty->water_meter_number,
            'electricity_meter_number' => $responseProperty->electricity_meter_number,
            'deed_number' => $responseProperty->deed_number ? asset($responseProperty->deed_number) : null,
        ], $characteristics);

        TenantActivity::emit($request, 'property.updated', 'user_properties', $property->id, $old ?? null, [
            'id' => $property->id, 'title' => optional($property->contents->first())->title
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'property' => $formattedProperty,
        ], 200);
    }

    public function destroy($id)
    {
        try {
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

            // TenantActivity::emit($request, 'property.deleted', 'user_properties', $property->id, $property->toArray(), null);

            return response()->json([
                'status' => 'success',
                'message' => 'Property deleted successfully'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function toggleFeatured($id)
    {
        try {
            $property = Property::findOrFail($id);

            $property->featured = !$property->featured;
            $property->save();
            Audit::property($property->user_id, $property->id, 'custom', "toggle featured -> ".($property->featured ? 'on' : 'off'));

            return response()->json([
                'status' => 'success',
                'message' => 'Property featured status updated',
                'data' => ['featured' => $property->featured]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $property = Property::findOrFail($id);

            $property->status = !$property->status;
            $property->save();
            return response()->json([
                'status' => 'success',
                'message' => 'Property status updated successfully',
                'data' => ['status' => $property->status]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Property not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
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

    /**
     * Upload deed image for property
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDeedImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'deed_image' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
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

            $directory = public_path('assets/img/property/deeds');

            // Create directory if it doesn't exist
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            // Move file to directory
            $file->move($directory, $fileName);

            // Return the relative path
            $filePath = 'assets/img/property/deeds/' . $fileName;

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


    public function index(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();

        // Resolve tenant owner and include all employees under that tenant
        $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
        $ownerId = (int) $owner->id;

        $allowedUserIds = [$ownerId];
        try {
            $cacheKey = "tenant_employees_{$ownerId}";
            $employeeIds = Cache::remember($cacheKey, 300, function () use ($ownerId) {
                return \App\Models\User::where('tenant_id', $ownerId)
                    ->where('account_type', 'employee')
                    ->pluck('id')
                    ->toArray();
            });
            $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
        } catch (\Throwable $e) {}

        // Build the properties query
        // OPTIMIZED: Added missing eager loading for galleryImages and UserPropertyCharacteristics
        // to prevent N+1 queries when formatting response
        $propertiesQuery = Property::with([
            'category:id,name',
            'user:id,username,first_name,last_name,email,account_type',
            'contents:id,property_id,title,slug,address,description',
            'proertyAmenities.amenity:id,name',
            'creator:id,first_name,last_name,username,email,account_type',
            'galleryImages:id,property_id,image', // Added to prevent N+1
            'UserPropertyCharacteristics:id,property_id', // Added if needed for filtering
        ])
            ->whereIn('user_id', $allowedUserIds);

        // Apply purpose filter if provided
        if ($request->has('purposes_filter') && !empty($request->purposes_filter)) {
            $propertiesQuery->where('purpose', $request->purposes_filter);
        }

        // Apply specifics filters
        if ($request->has('price_from') && !empty($request->price_from)) {
            $propertiesQuery->where('price', '>=', $request->price_from);
        }
        if ($request->has('price_to') && !empty($request->price_to)) {
            $propertiesQuery->where('price', '<=', $request->price_to);
        }
        if ($request->has('area_from') && !empty($request->area_from)) {
            $propertiesQuery->where('area', '>=', $request->area_from);
        }
        if ($request->has('purpose') && !empty($request->purpose)) {
            $propertiesQuery->where('purpose', $request->purpose);
        }
        if ($request->has('type') && !empty($request->type)) {
            $propertiesQuery->where('type', $request->type);
        }
        if ($request->has('beds') && !empty($request->beds)) {
            $propertiesQuery->where('beds', $request->beds);
        }
        if ($request->has('bath') && !empty($request->bath)) {
            $propertiesQuery->where('bath', $request->bath);
        }
        if ($request->has('features') && !empty($request->features)) {
            $featuresArray = explode(',', $request->features);
            foreach ($featuresArray as $feature) {
                $feature = trim($feature);
                $propertiesQuery->whereJsonContains('features', $feature);
            }
        }

        // Apply UserPropertyCharacteristic filters
        // OPTIMIZED: Combine all filters into a single whereHas query instead of N separate queries
        $characteristicFilters = [
            'private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
            'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
            'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 'floors',
            'bathrooms', 'rooms', 'building_age'
        ];

        // Check if any characteristic filters are present
        $hasCharacteristicFilter = false;
        $activeFilters = [];
        foreach ($characteristicFilters as $filter) {
            if ($request->has($filter) && !empty($request->$filter)) {
                $hasCharacteristicFilter = true;
                $activeFilters[$filter] = $request->$filter;
            }
        }

        // OPTIMIZED: Use join instead of whereHas for better performance
        // Joins are faster than subqueries created by whereHas
        if ($hasCharacteristicFilter) {
            $propertiesQuery->join('user_property_characteristics as upc', 'upc.property_id', '=', 'user_properties.id');
            foreach ($activeFilters as $filter => $value) {
                $propertiesQuery->where("upc.{$filter}", $value);
            }
            // Use distinct to prevent duplicate rows from join
            $propertiesQuery->distinct();
            // Ensure we select from the main table
            $propertiesQuery->select('user_properties.*');
        }

        // Apply sorting
        $sortParam = $request->input('sort', 'default');
        switch ($sortParam) {
            case 'price_asc':
                $propertiesQuery->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $propertiesQuery->orderBy('price', 'desc');
                break;
            case 'area_asc':
                $propertiesQuery->orderBy('area', 'asc');
                break;
            case 'area_desc':
                $propertiesQuery->orderBy('area', 'desc');
                break;
            case 'reorder_asc':
                $propertiesQuery->orderBy('reorder', 'asc');
                break;
            case 'reorder_desc':
                $propertiesQuery->orderBy('reorder', 'desc');
                break;
            case 'reorder_featured_asc':
                $propertiesQuery->orderBy('reorder_featured', 'asc');
                break;
            case 'reorder_featured_desc':
                $propertiesQuery->orderBy('reorder_featured', 'desc');
                break;
            case 'created_at_asc':
                $propertiesQuery->orderBy('created_at', 'asc');
                break;
            case 'created_at_desc':
                $propertiesQuery->orderBy('created_at', 'desc');
                break;
            default:
                // Default: reorder_featured desc, then reorder asc (current behavior)
                $propertiesQuery->orderBy('reorder_featured', 'desc')->orderBy('reorder', 'asc');
        }

        $properties = $propertiesQuery->paginate(10);

        // === GA4: views per property (last 30 days by default) ===
        $tenantId  = $owner->username;                     // align GA context with tenant owner
        $days      = (int) $request->input('days', 30);   // override with ?days=7 if you want
        $startDate = Carbon::now()->subDays($days);
        $endDate   = Carbon::now();

        // Collect slugs for the current page
        $slugs = $properties->getCollection()
            ->map(fn ($p) => optional($p->contents->first())->slug)
            ->filter()
            ->values();

        // Build candidate paths for each slug (adjust prefixes to match your frontend routes)
        $paths = [];
        foreach ($slugs as $slug) {
            $paths[] = "/property/{$slug}";
            $paths[] = "/ar/property/{$slug}";
            $paths[] = "/en/property/{$slug}";
        }

        // Query GA4 with backend filtering to get ALL data (CACHED - 5 minutes)
        $viewsBySlug = [];
        if (!empty($paths)) {
            $cacheKey = "ga_views_{$tenantId}_{$days}_" . md5(implode(',', $slugs->toArray()));
            $viewsBySlug = Cache::remember($cacheKey, 300, function () use ($analytics, $startDate, $endDate, $paths, $tenantId, $slugs) {
                $result = [];
                try {
                    $allData = $analytics->getAllAnalyticsWithFilters(
                        $startDate,
                        $endDate,
                        [
                            'tenant_ids' => [$tenantId],
                            'exclude_empty_tenant' => false,
                            'limit' => count($paths) * 10,
                        ]
                    );

                    // Build a map of slug => total views
                    foreach ($allData['data'] as $item) {
                        $path = $item['path'];
                        $views = (int) $item['views'];
                        
                        // Extract slug from path and add to slug view map
                        foreach ($slugs as $slug) {
                            if (strpos($path, $slug) !== false) {
                                $result[$slug] = ($result[$slug] ?? 0) + $views;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Google Analytics error in admin PropertyController', [
                        'tenant' => $tenantId,
                        'error' => $e->getMessage(),
                    ]);
                }
                return $result;
            });
        }

        // ===== Get filter options (CACHED - 1 hour) =====
        $cacheKey = "property_filter_options_{$ownerId}";
        $filterOptions = Cache::remember($cacheKey, 3600, function () use ($allowedUserIds) {
            // OPTIMIZED: Combined price and area stats in single query
            $stats = Property::whereIn('user_id', $allowedUserIds)
                ->where(function($q) {
                    $q->whereNotNull('price')->orWhereNotNull('area');
                })
                ->selectRaw('
                    MIN(price) as min_price,
                    MAX(price) as max_price,
                    MIN(area) as min_area
                ')
                ->first();

            $priceRange = [
                'min' => $stats->min_price ?: 0,
                'max' => $stats->max_price ?: 0,
            ];

            $areaRange = [
                'min' => $stats->min_area ?: 0,
            ];

            // OPTIMIZED: Get all distinct filter values in a single query using UNION
            // This reduces from 4 separate queries to 1 query
            $filterValues = DB::table('user_properties')
                ->whereIn('user_id', $allowedUserIds)
                ->selectRaw("'purpose' as filter_type, purpose as value")
                ->whereNotNull('purpose')
                ->where('purpose', '!=', '')
                ->distinct()
                ->union(
                    DB::table('user_properties')
                        ->whereIn('user_id', $allowedUserIds)
                        ->selectRaw("'type' as filter_type, type as value")
                        ->whereNotNull('type')
                        ->where('type', '!=', '')
                        ->distinct()
                )
                ->union(
                    DB::table('user_properties')
                        ->whereIn('user_id', $allowedUserIds)
                        ->selectRaw("'beds' as filter_type, CAST(beds AS CHAR) as value")
                        ->whereNotNull('beds')
                        ->distinct()
                )
                ->union(
                    DB::table('user_properties')
                        ->whereIn('user_id', $allowedUserIds)
                        ->selectRaw("'bath' as filter_type, CAST(bath AS CHAR) as value")
                        ->whereNotNull('bath')
                        ->distinct()
                )
                ->get()
                ->groupBy('filter_type');

            $availablePurposes = $filterValues->get('purpose', collect())->pluck('value')->unique()->values()->toArray();
            $availableTypes = $filterValues->get('type', collect())->pluck('value')->unique()->values()->toArray();
            $availableBeds = $filterValues->get('beds', collect())->pluck('value')->map(fn($v) => (int)$v)->unique()->sort()->values()->toArray();
            $availableBath = $filterValues->get('bath', collect())->pluck('value')->map(fn($v) => (int)$v)->unique()->sort()->values()->toArray();

            // Extract unique features from JSON arrays
            $allFeatures = Property::whereIn('user_id', $allowedUserIds)
                ->whereNotNull('features')
                ->pluck('features')
                ->flatten()
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $availableFeatures = array_values($allFeatures);
            sort($availableFeatures);

            // OPTIMIZED: Get UserPropertyCharacteristic filter options using a single query with conditional aggregation
            // Instead of N queries (one per field), use a single query with CASE statements
            $propertyIds = Property::whereIn('user_id', $allowedUserIds)
                ->pluck('id');

            $characteristicFilterOptions = [];
            $characteristicFields = [
                'private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
                'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
                'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 'floors',
                'bathrooms', 'rooms', 'building_age'
            ];

            // OPTIMIZED: Batch load all characteristic values in fewer queries
            // Group fields by type (boolean vs numeric) to optimize queries
            $booleanFields = ['private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
                'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
                'driver_room', 'swimming_pool', 'kitchen'];
            $numericFields = ['floor_number', 'floors', 'bathrooms', 'rooms', 'building_age'];

            // Get all characteristics data in one query
            $allCharacteristics = UserPropertyCharacteristic::whereIn('property_id', $propertyIds)
                ->select($characteristicFields)
                ->get();

            // Process in PHP (faster than multiple DB queries for small datasets)
            foreach ($characteristicFields as $field) {
                $values = $allCharacteristics
                    ->pluck($field)
                    ->filter(fn($v) => !is_null($v))
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                if (!empty($values)) {
                    $characteristicFilterOptions[$field] = $values;
                }
            }

            return [
                'purposes' => $availablePurposes,
                'price_range' => $priceRange,
                'area_range' => $areaRange,
                'types' => $availableTypes,
                'beds' => $availableBeds,
                'bath' => $availableBath,
                'features' => $availableFeatures,
                'characteristics' => $characteristicFilterOptions,
            ];
        });

        // Extract cached values
        $availablePurposes = $filterOptions['purposes'];
        $priceRange = $filterOptions['price_range'];
        $areaRange = $filterOptions['area_range'];
        $availableTypes = $filterOptions['types'];
        $availableBeds = $filterOptions['beds'];
        $availableBath = $filterOptions['bath'];
        $availableFeatures = $filterOptions['features'];
        $characteristicFilterOptions = $filterOptions['characteristics'];

        $specificsFilters = [
            'price_range' => $priceRange,
            'area_range' => $areaRange,
            'purpose' => $availablePurposes,
            'type' => $availableTypes,
            'beds' => $availableBeds,
            'bath' => $availableBath,
            'features' => array_values($availableFeatures),
            'characteristics' => $characteristicFilterOptions,
        ];

        // === Format response ===
        $formattedProperties = $properties->getCollection()->map(function ($property) use ($viewsBySlug) {
            $content = optional($property->contents->first());
            $slug    = $content->slug;

            return [
                'id'               => $property->id,
                'visits'           => (int) ($viewsBySlug[$slug] ?? 0), // << here
                'title'            => $content->title ?? 'No Title',
                'address'          => $content->address ?? 'No Address',
                'slug'             => $slug,
                'price'            => $property->price,
                'type'             => $property->type,
                'beds'             => $property->beds,
                'bath'             => $property->bath,
                'area'             => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : null,
                'transaction_type' => $property->purpose,
                'features'         => $property->features,
                'status'           => $property->status,
                'featured_image'   => asset($property->featured_image),
                'featured'         => (bool) $property->featured,
                'show_reservations' => (bool) $property->show_reservations,
                'created_at'       => $property->created_at->toISOString(),
                'updated_at'       => $property->updated_at->toISOString(),
                'payment_method'   => $property->payment_method,
                'creator' => $property->creator ? [
                    'id'   => $property->creator->id,
                    'name' => trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? '')) ?: ($property->creator->username ?? $property->creator->email),
                    'type' => $property->creator->account_type,
                ] : null,
            ];
        });

        $totalReorderFeatured = Property::whereIn('user_id', $allowedUserIds)
            ->where('featured', 1)
            ->where('reorder_featured', '>', 0)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'properties' => $formattedProperties,
                'purposes_filter' => $availablePurposes,
                'specifics_filters' => $specificsFilters,
                'total_reorder_featured' => $totalReorderFeatured,
                'pagination' => [
                    'total'        => $properties->total(),
                    'per_page'     => $properties->perPage(),
                    'current_page' => $properties->currentPage(),
                    'last_page'    => $properties->lastPage(),
                    'from'         => $properties->firstItem(),
                    'to'           => $properties->lastItem(),
                ]
            ]
        ], 200);
    }

    /**
     * Get available units (units without active or draft rentals)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function availableUnits(Request $request): JsonResponse
    {
        try {
            $userId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            // Validate request parameters
            $request->validate([
                'project_id' => 'nullable|integer',
                'building_id' => 'nullable|integer',
                'per_page' => 'nullable|integer|min:1|max:100',
                'search' => 'nullable|string|max:255',
            ]);

            $perPage = $request->get('per_page', 50);
            $search = $request->get('search');
            $projectId = $request->get('project_id');
            $buildingId = $request->get('building_id');

            // Get properties (units) that don't have active or draft rentals
            $query = Property::with(['project.contents', 'building:id,name', 'contents'])
                ->where('user_id', $userId)
                ->whereDoesntHave('rentals', function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->whereIn('status', ['active', 'draft']);
                })
                ->where('property_status', '!=', 'rented');

            // Apply filters
            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            if ($buildingId) {
                $query->where('building_id', $buildingId);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('contents', function ($contentQuery) use ($search) {
                        $contentQuery->where('title', 'like', "%{$search}%");
                    });
                });
            }

            $properties = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Format response
            $formattedProperties = $properties->map(function ($property) {
                $content = optional($property->contents)->first();
                $projectContent = optional(optional($property->project)->contents)->first();

                // Use the already-loaded building relationship (loaded via ->with(['building:id,name']))
                $buildingName = $property->building ? $property->building->name : 'N/A';

                return [
                    'id' => $property->id,
                    'title' => $content->title ?? 'N/A',
                    'building' => [
                        'id' => $property->building_id,
                        'name' => $buildingName,
                    ],
                    'project' => [
                        'id' => $property->project_id,
                        'name' => $projectContent->title ?? 'N/A',
                    ],
                    'property_status' => $property->property_status,
                    'show_reservations' => (bool) $property->show_reservations,
                    'is_available' => true,
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedProperties,
                'pagination' => [
                    'current_page' => $properties->currentPage(),
                    'last_page' => $properties->lastPage(),
                    'per_page' => $properties->perPage(),
                    'total' => $properties->total(),
                    'from' => $properties->firstItem(),
                    'to' => $properties->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching available units', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error fetching available units',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

}
