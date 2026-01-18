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
use App\Services\DatabaseVersionService;
use App\Services\PropertyFilterOptionsService;
use Carbon\Carbon;
use App\Services\AlibabaOssService;
use App\Services\MembershipCacheService;


use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PropertiesImport;

class PropertyController extends Controller
{
    public function bulkImport(Request $request)
    {
        try {
            // Validate file presence and type
            $request->validate([
                'file' => 'required|mimes:xlsx,csv|max:10240', // 10MB max
            ], [
                'file.required' => 'A file is required for import. Please select an Excel or CSV file.',
                'file.mimes' => 'The file must be an Excel (.xlsx) or CSV (.csv) file.',
                'file.max' => 'The file size must not exceed 10MB. Please use a smaller file or split it into multiple files.',
            ]);

            $user = auth()->user();

            // Check authentication
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'IMPORT_PERMISSION_DENIED',
                    'message' => 'Authentication required to import properties',
                    'timestamp' => now()->toIso8601String(),
                ], 401);
            }

            // Check if user has active membership (cached)
            $membership = MembershipCacheService::getActiveMembership($user->id);

            if (!$membership || !$membership->package) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'IMPORT_PERMISSION_DENIED',
                    'message' => 'No active package found for the user',
                    'details' => [
                        'user_id' => $user->id,
                        'suggestion' => 'Please activate a membership package to import properties.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 403);
            }

            // Check property limit before processing the file
            $realEstateLimit = $membership->package->real_estate_limit_number;
            $currentPropertyCount = Property::where('user_id', $user->id)
                ->where('completion_status', 'complete')
                ->count();

            // Estimate row count from uploaded file
            try {
                // Validate file can be read
                if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'IMPORT_FILE_INVALID',
                        'message' => 'Invalid or corrupted file uploaded',
                        'details' => [
                            'suggestion' => 'Please ensure the file is a valid Excel (.xlsx) or CSV (.csv) file and try again.',
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

                // Smart Method: Calculate the exact number of rows with data
                // This avoids reading thousands of empty rows
                $filePath = $request->file('file')->getPathname();
                $fileSize = $request->file('file')->getSize();
                
                // Check file size (10MB = 10485760 bytes)
                if ($fileSize > 10485760) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'IMPORT_FILE_TOO_LARGE',
                        'message' => 'File size exceeds the maximum allowed size',
                        'details' => [
                            'file_size' => $fileSize,
                            'max_size' => 10485760,
                            'max_size_mb' => '10MB',
                            'suggestion' => 'Please split your file into smaller files (max 10MB each) or reduce the number of rows.',
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

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
                
                // Required fields for a complete property
                $requiredFields = ['title', 'price', 'address', 'description', 'purpose', 'type', 'area'];
                
                // Count rows that will be complete (have all required fields)
                // Only complete properties count toward the limit
                $incomingCompleteCount = $firstSheet->filter(function($row) use ($requiredFields) {
                    $rowArray = $row->toArray();
                    
                    // Skip rows marked as empty by prepareForValidation
                    if (isset($rowArray['_skip_empty_row']) && $rowArray['_skip_empty_row'] === true) {
                        return false;
                    }
                    
                    // Skip rows where title is the dummy empty marker
                    if (isset($rowArray['title']) && $rowArray['title'] === '_EMPTY_ROW_SKIP_') {
                        return false;
                    }
                    
                    // Skip completely empty rows
                    $hasData = !empty(array_filter($rowArray, function($value) {
                        return !is_null($value) && $value !== '';
                    }));
                    
                    if (!$hasData) {
                        return false;
                    }
                    
                    // Check if this row has all required fields (will be complete)
                    foreach ($requiredFields as $field) {
                        $value = $rowArray[$field] ?? null;
                        if (is_null($value) || (is_string($value) && trim($value) === '') || $value === '') {
                            return false; // Missing required field - will be incomplete
                        }
                    }
                    
                    // Validate numeric fields
                    if (isset($rowArray['price']) && !is_numeric($rowArray['price'])) {
                        return false; // Invalid price - will fail validation
                    }
                    if (isset($rowArray['area']) && (!is_numeric($rowArray['area']) || $rowArray['area'] < 1)) {
                        return false; // Invalid area - will fail validation
                    }
                    
                    // Validate enum fields
                    if (isset($rowArray['purpose']) && !in_array($rowArray['purpose'], ['sale', 'rent'])) {
                        return false; // Invalid purpose - will fail validation
                    }
                    if (isset($rowArray['type']) && !in_array($rowArray['type'], ['residential', 'commercial'])) {
                        return false; // Invalid type - will fail validation
                    }
                    
                    // All required fields present and valid - will be complete
                    return true;
                })->count();
                
                // Only check limit for complete properties
                // Incomplete properties don't count toward the limit
                // Block only if complete properties would exceed the limit
                if (!is_null($realEstateLimit) && $incomingCompleteCount > 0 && ($currentPropertyCount + $incomingCompleteCount) > $realEstateLimit) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 'IMPORT_PERMISSION_DENIED',
                        'message' => 'Bulk import would exceed your property listing limit',
                        'details' => [
                            'limit' => $realEstateLimit,
                            'current_count' => $currentPropertyCount,
                            'incoming_complete_count' => $incomingCompleteCount,
                            'available_slots' => max(0, $realEstateLimit - $currentPropertyCount),
                            'suggestion' => 'Please remove some existing properties or upgrade your package to increase the limit. Note: Incomplete properties do not count toward your limit.',
                        ],
                        'timestamp' => now()->toIso8601String(),
                    ], 403);
                }
            } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'IMPORT_FILE_INVALID',
                    'message' => 'Failed to read uploaded file',
                    'details' => [
                        'error' => config('app.debug') ? $e->getMessage() : 'The file format is invalid or corrupted.',
                        'suggestion' => 'Please ensure the file is a valid Excel (.xlsx) or CSV (.csv) file. Try opening it in Excel first to verify it\'s not corrupted.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'IMPORT_FILE_INVALID',
                    'message' => 'Failed to process uploaded file',
                    'details' => [
                        'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while reading the file.',
                        'suggestion' => 'Please check that the file is not corrupted and try again.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            try {
                // Use the same smart limit for the actual import
                $import = new PropertiesImport($user->id, $highestRow);
                Excel::import($import, $request->file('file'));

                $failures = $import->sheetImport->failures();
                $errors = $import->sheetImport->errors();
                $detailedErrors = [];

                // Ensure failures is an array
                if (!is_array($failures) && !is_iterable($failures)) {
                    $failures = [];
                }

                // Collect Validation Failures with enhanced structure
                foreach ($failures as $failure) {
                    $failureErrors = $failure->errors();
                    $failureValues = $failure->values();
                    
                    // Ensure failureErrors is an array
                    if (!is_array($failureErrors)) {
                        continue;
                    }
                    
                    // Extract field name from error message if possible
                    foreach ($failureErrors as $field => $errorMessages) {
                        // Ensure errorMessages is an array
                        if (!is_array($errorMessages)) {
                            $errorMessages = [$errorMessages];
                        }
                        foreach ($errorMessages as $errorMessage) {
                            $detailedErrors[] = [
                                'row' => $failure->row(),
                                'field' => $field,
                                'error' => $errorMessage,
                                'expected' => $this->getExpectedFormat($field),
                                'actual' => $failureValues[$field] ?? null,
                                'severity' => 'error',
                                'suggestion' => $this->getSuggestion($field, $errorMessage),
                            ];
                        }
                    }
                }

                // Ensure errors is an array
                if (!is_array($errors) && !is_iterable($errors)) {
                    $errors = [];
                }

                // Collect Logic Errors (Exceptions) with enhanced structure
                foreach ($errors as $error) {
                    $message = $error->getMessage();
                    $row = null;
                    $field = null;
                    
                    // Extract row number if present in exception message
                    if (preg_match('/Row (\d+):/', $message, $matches)) {
                        $row = (int)$matches[1];
                    }

                    // Try to extract field name from error message
                    if (preg_match('/Invalid (\w+)/i', $message, $fieldMatches)) {
                        $field = strtolower($fieldMatches[1]);
                    }

                    $detailedErrors[] = [
                        'row' => $row,
                        'field' => $field,
                        'error' => $message,
                        'expected' => null,
                        'actual' => null,
                        'severity' => 'error',
                        'suggestion' => 'Please check the row data and ensure all required fields are provided correctly.',
                    ];
                }

                $importedCount = $import->sheetImport->importedCount;
                $updatedCount = $import->sheetImport->updatedCount ?? 0;
                $incompleteCount = $import->sheetImport->incompleteCount ?? 0;
                $failedCount = count($detailedErrors);
                $totalProcessed = $importedCount + $updatedCount;

                // Get incomplete properties details
                $incompleteProperties = [];
                if ($incompleteCount > 0) {
                    $importBatchId = $import->sheetImport->importBatchId ?? null;
                    if ($importBatchId) {
                        $incompleteProperties = Property::where('user_id', $user->id)
                            ->where('import_batch_id', $importBatchId)
                            ->where('completion_status', 'incomplete')
                            ->with(['contents:id,property_id,title'])
                            ->get(['id', 'missing_fields', 'validation_errors', 'created_at'])
                            ->map(function($property) {
                                // Ensure missing_fields and validation_errors are arrays
                                $missingFields = $property->missing_fields ?? [];
                                if (is_string($missingFields)) {
                                    $decoded = json_decode($missingFields, true);
                                    $missingFields = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                                } elseif (!is_array($missingFields)) {
                                    $missingFields = [];
                                }
                                
                                $validationErrors = $property->validation_errors ?? [];
                                if (is_string($validationErrors)) {
                                    $decoded = json_decode($validationErrors, true);
                                    $validationErrors = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
                                } elseif (!is_array($validationErrors)) {
                                    $validationErrors = [];
                                }
                                
                                return [
                                    'id' => $property->id,
                                    'title' => $property->contents->first()?->title ?? 'Untitled',
                                    'missing_fields' => $missingFields,
                                    'validation_errors' => $validationErrors,
                                    'created_at' => $property->created_at,
                                ];
                            })
                            ->toArray();
                    }
                }

                // If there are actual failures (validation errors that prevented creation), return 422
                if ($failedCount > 0) {
                    $message = "Import completed with {$failedCount} validation error(s)";
                    if ($incompleteCount > 0) $message .= " and {$incompleteCount} incomplete property/properties";
                    $message .= ". ";
                    if ($importedCount > 0) $message .= "Created: {$importedCount} properties. ";
                    if ($updatedCount > 0) $message .= "Updated: {$updatedCount} properties.";

                    return response()->json([
                        'status' => 'partial_success',
                        'code' => 'IMPORT_VALIDATION_ERROR',
                        'message' => $message,
                        'imported_count' => $importedCount,
                        'updated_count' => $updatedCount,
                        'incomplete_count' => $incompleteCount,
                        'failed_count' => $failedCount,
                        'incomplete_properties' => $incompleteProperties,
                        'errors' => $detailedErrors,
                        'timestamp' => now()->toIso8601String(),
                    ], 422);
                }

                // If there are incomplete properties but no failures, return 200 with partial_success
                if ($incompleteCount > 0) {
                    $message = "Import completed with {$incompleteCount} incomplete property/properties";
                    $message .= ". ";
                    if ($importedCount > 0) $message .= "Created: {$importedCount} properties. ";
                    if ($updatedCount > 0) $message .= "Updated: {$updatedCount} properties.";

                    return response()->json([
                        'status' => 'partial_success',
                        'code' => 'IMPORT_PARTIAL_SUCCESS',
                        'message' => $message,
                        'imported_count' => $importedCount,
                        'updated_count' => $updatedCount,
                        'incomplete_count' => $incompleteCount,
                        'failed_count' => 0,
                        'incomplete_properties' => $incompleteProperties,
                        'errors' => [],
                        'timestamp' => now()->toIso8601String(),
                    ], 200);
                }

                $message = "Import successful. ";
                if ($importedCount > 0) $message .= "Created: {$importedCount} properties. ";
                if ($updatedCount > 0) $message .= "Updated: {$updatedCount} properties.";

                return response()->json([
                    'status' => 'success',
                    'code' => 'IMPORT_SUCCESS',
                    'message' => $message,
                    'imported_count' => $importedCount,
                    'updated_count' => $updatedCount,
                    'incomplete_count' => $incompleteCount,
                    'failed_count' => 0,
                    'incomplete_properties' => $incompleteProperties,
                    'errors' => [],
                    'timestamp' => now()->toIso8601String(),
                ]);

            } catch (\Exception $e) {
                Log::error('Bulk property import processing error', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'IMPORT_PROCESSING_ERROR',
                    'message' => 'An error occurred during import processing',
                    'details' => [
                        'user_id' => $user->id,
                        'error' => config('app.debug') ? $e->getMessage() : 'A critical error occurred while processing the import. Please try again or contact support.',
                        'suggestion' => 'Please verify your file format and data, then try again. If the problem persists, contact support.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'IMPORT_VALIDATION_ERROR',
                'message' => 'File validation failed',
                'errors' => $e->errors(),
                'details' => [
                    'user_id' => auth()->id(),
                    'suggestion' => 'Please ensure you are uploading a valid Excel (.xlsx) or CSV (.csv) file that does not exceed 10MB.',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Bulk property import critical error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 'IMPORT_PROCESSING_ERROR',
                'message' => 'A critical error occurred during import',
                'details' => [
                    'user_id' => auth()->id(),
                    'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get expected format for a field (helper for error messages)
     */
    private function getExpectedFormat(string $field): ?string
    {
        $formats = [
            'title' => 'Non-empty text string (max 255 characters)',
            'price' => 'Positive numeric value (e.g., 500000)',
            'address' => 'Non-empty text string',
            'description' => 'Non-empty text string',
            'purpose' => 'Either "sale" or "rent"',
            'type' => 'Either "residential" or "commercial"',
            'area' => 'Positive numeric value (e.g., 150)',
            'beds' => 'Positive integer (e.g., 3)',
            'bath' => 'Positive integer (e.g., 2)',
            'status' => 'Either "1" (active) or "0" (inactive)',
            'featured' => 'Either "Yes", "1", "true" or "No", "0", "false"',
            'category_name' => 'Valid category name from your categories list',
            'city_name' => 'Valid city name from your cities list',
            'district_name' => 'Valid district name from your districts list',
            'featured_image' => 'Valid URL to an image file (http:// or https://)',
            'gallery_images' => 'Comma-separated list of image URLs',
            'video_url' => 'Valid URL to a video file',
        ];

        return $formats[$field] ?? 'Valid value according to field requirements';
    }

    /**
     * Get suggestion for fixing an error (helper for error messages)
     */
    private function getSuggestion(string $field, string $errorMessage): string
    {
        $suggestions = [
            'title' => 'Please provide a title for the property.',
            'price' => 'Please provide a valid positive number for the price (e.g., 500000).',
            'address' => 'Please provide a valid address for the property.',
            'description' => 'Please provide a description for the property.',
            'purpose' => 'Please specify either "sale" or "rent" as the purpose.',
            'type' => 'Please specify either "residential" or "commercial" as the type.',
            'area' => 'Please provide a valid positive number for the area (e.g., 150).',
            'category_name' => 'Please check that the category name exists in your categories list, or create it first.',
            'city_name' => 'Please check that the city name exists in your cities list, or create it first.',
            'district_name' => 'Please check that the district name exists in your districts list, or create it first.',
            'featured_image' => 'Please provide a valid image URL (must start with http:// or https://).',
        ];

        if (isset($suggestions[$field])) {
            return $suggestions[$field];
        }

        if (str_contains($errorMessage, 'required')) {
            return "Please provide a value for the {$field} field.";
        }

        if (str_contains($errorMessage, 'numeric')) {
            return "Please provide a valid numeric value for the {$field} field.";
        }

        if (str_contains($errorMessage, 'invalid') || str_contains($errorMessage, 'not found')) {
            return "Please check that the {$field} value is valid and exists in your system.";
        }

        return "Please check the {$field} field and ensure it meets the requirements.";
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
        $currentPropertyCount = Property::where('user_id', $user->id)
            ->where('completion_status', 'complete')
            ->count();

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
            'advertising_license' => $responseProperty->advertising_license,
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


    public function show(Request $request, $id, GoogleAnalyticsService $analytics)
    {
        try {
            $user = $request->user();
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

            $days = (int) $request->query('days', 30);
            $cacheKey = "property_api_{$id}_owner_{$ownerId}_v1_days_{$days}";
            $cacheTtl = 300; // 5 minutes

            $response = Cache::remember($cacheKey, $cacheTtl, function () use ($id, $analytics, $days, $allowedUserIds) {
                $property = Property::with([
                    'category',
                    'user',
                    'contents' => fn($q) => $q->limit(1)->orderBy('language_id'),
                    'galleryImages',
                    'proertyAmenities.amenity',
                    'UserPropertyCharacteristics',
                    'creator',
                    'building',
                ])->whereIn('user_id', $allowedUserIds)->findOrFail($id);

                $content = $property->contents->first();
                $characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];

                // Fetch views from Google Analytics (CACHED - 5 minutes)
                $views = 0;
                if ($content && $content->slug && $property->user) {
                    $tenantId = $property->user->username;
                    $gaCacheKey = "ga_views_property_{$id}_{$tenantId}_{$days}_{$content->slug}";
                    
                    $views = Cache::remember($gaCacheKey, 300, function () use ($analytics, $days, $content, $tenantId) {
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
                            Log::error('Google Analytics error in admin PropertyController show', [
                                'property_id' => $property->id ?? null,
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
                    'slug' => optional($content)->slug ?? '',
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
                    'floor_planning_image' => $property->floor_planning_image_urls,
                    'gallery' => $property->gallery_urls,
                    'description' => optional($content)->description ?? '',
                    'latitude' => $property->latitude ? (float) $property->latitude : null,
                    'longitude' => $property->longitude ? (float) $property->longitude : null,
                    'location' => [
                        'latitude' => $property->latitude ? (float) $property->latitude : null,
                        'longitude' => $property->longitude ? (float) $property->longitude : null,
                    ],
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
                    'advertising_license' => $property->advertising_license,
                    'created_at' => $property->created_at->toISOString(),
                    'updated_at' => $property->updated_at->toISOString(),
                    'creator' => $property->creator ? [
                        'id'   => $property->creator->id,
                        'name' => trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? '')) ?: ($property->creator->username ?? $property->creator->email),
                        'type' => $property->creator->account_type,
                    ] : null,
                ], $characteristics);

                return [
                    'status' => 'success',
                    'data' => [
                        'property' => $formattedProperty
                    ]
                ];
            });

            // Build response with HTTP cache headers
            $responseObj = response()->json($response);
            $etag = md5($responseObj->getContent());
            
            return $responseObj->withHeaders([
                'Cache-Control' => 'private, max-age=' . $cacheTtl,
                'ETag' => $etag,
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
        $currentPropertyCount = Property::where('user_id', $owner->id)
            ->where('completion_status', 'complete')
            ->count();

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
            'advertising_license' => 'nullable|string',
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
                'advertising_license',
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
            'advertising_license' => $responseProperty->advertising_license,
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
            'advertising_license' => 'nullable|string',
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
            'floor_planning_image' => $responseProperty->floor_planning_image_urls,
            'gallery' => $responseProperty->gallery_urls,
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
            'advertising_license' => $responseProperty->advertising_license,
        ], $characteristics);

        TenantActivity::emit($request, 'property.updated', 'user_properties', $property->id, $old ?? null, [
            'id' => $property->id, 'title' => optional($property->contents->first())->title
        ]);

        // Invalidate cache for this property (all days variants)
        Cache::forget("property_api_{$id}_v1_days_7");
        Cache::forget("property_api_{$id}_v1_days_30");
        Cache::forget("property_api_{$id}_v1_days_90");
        Cache::forget("property_api_{$id}_v1_days_365");

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

            // Invalidate cache for this property (all days variants)
            Cache::forget("property_api_{$id}_v1_days_7");
            Cache::forget("property_api_{$id}_v1_days_30");
            Cache::forget("property_api_{$id}_v1_days_90");
            Cache::forget("property_api_{$id}_v1_days_365");

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
        // PERFORMANCE: Start timing for query performance monitoring
        $startTime = microtime(true);
        $queryStartCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        
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

        // OPTIMIZED: Track JOINs to avoid duplicates and ensure proper query structure
        $hasContentJoin = false;
        
        // Check if we'll need a content JOIN (for city/district/search filters)
        $willNeedContentJoin = $request->has('city_id') || $request->has('district_id') || 
                               ($request->has('search') && !empty($request->search));

        // Build the properties query
        // OPTIMIZED: Added missing eager loading for galleryImages and UserPropertyCharacteristics
        // to prevent N+1 queries when formatting response
        // Skip contents eager loading if we'll use a JOIN instead
        $eagerLoadRelations = [
            'category:id,name',
            'user:id,username,first_name,last_name,email,account_type',
            'proertyAmenities' => function($q) {
                $q->with('amenity:id,name'); // Explicit nested eager loading to prevent N+1
            },
            'creator:id,first_name,last_name,username,email,account_type',
            'galleryImages:id,property_id,image', // Added to prevent N+1
            'UserPropertyCharacteristics:id,property_id', // Added if needed for filtering
        ];
        
        // Only eager load contents if we won't be using a JOIN
        if (!$willNeedContentJoin) {
            $eagerLoadRelations['contents'] = function($q) {
                $q->select('id', 'property_id', 'title', 'slug', 'address', 'description')
                  ->orderBy('id', 'asc')
                  ->limit(1); // Only load first content since we only use first() in response
            };
        }
        
        $propertiesQuery = Property::with($eagerLoadRelations)
            ->whereIn('user_id', $allowedUserIds)
            ->where('completion_status', 'complete');
        
        // Ensure only properties with content records are returned (when no content JOIN is used)
        // This prevents "No Title" and "No Address" fallback values
        if (!$willNeedContentJoin) {
            $propertiesQuery->whereHas('contents', function($q) {
                // Ensure content has non-empty title and address
                $q->whereNotNull('title')
                  ->where('title', '!=', '')
                  ->whereNotNull('address')
                  ->where('address', '!=', '');
            });
        }
        
        $contentJoinAlias = 'pc_content'; // Single alias for all content joins
        $useInnerJoin = false; // Determine if we need INNER JOIN (for city/district filters)

        // Check if city_id or district_id filters are present (require INNER JOIN)
        $hasCityFilter = $request->has('city_id') && !empty($request->city_id);
        $hasDistrictFilter = $request->has('district_id') && !empty($request->district_id);
        if ($hasCityFilter || $hasDistrictFilter) {
            $useInnerJoin = true;
        }

        // Filter by city_id
        // OPTIMIZED: Use INNER JOIN instead of whereHas for better performance
        if ($hasCityFilter) {
            if (!$hasContentJoin) {
                $propertiesQuery->join('user_property_contents as ' . $contentJoinAlias, 
                    $contentJoinAlias . '.property_id', '=', 'user_properties.id');
                $hasContentJoin = true;
            }
            $propertiesQuery->where($contentJoinAlias . '.city_id', $request->city_id);
        }

        // Filter by district_id (stored as state_id in PropertyContent)
        // OPTIMIZED: Use INNER JOIN instead of whereHas for better performance
        if ($hasDistrictFilter) {
            if (!$hasContentJoin) {
                $propertiesQuery->join('user_property_contents as ' . $contentJoinAlias, 
                    $contentJoinAlias . '.property_id', '=', 'user_properties.id');
                $hasContentJoin = true;
            }
            $propertiesQuery->where($contentJoinAlias . '.state_id', $request->district_id);
        }

        // Text search functionality (title, address, description, price)
        // OPTIMIZED: Use JOIN instead of whereHas for better performance
        // Use INNER JOIN if city/district filters are present, otherwise LEFT JOIN for search-only
        // OPTIMIZED: Prefer prefix matching for better index usage, fallback to full-text or LIKE
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = trim($request->search);
            if (!$hasContentJoin) {
                if ($useInnerJoin) {
                    $propertiesQuery->join('user_property_contents as ' . $contentJoinAlias, 
                        $contentJoinAlias . '.property_id', '=', 'user_properties.id');
                } else {
                    $propertiesQuery->leftJoin('user_property_contents as ' . $contentJoinAlias, 
                        $contentJoinAlias . '.property_id', '=', 'user_properties.id');
                }
                $hasContentJoin = true;
            }
            
            // OPTIMIZED: Use cached MySQL version check to avoid repeated queries
            $isMysql56Plus = DatabaseVersionService::isMysql56Plus();
            
            // OPTIMIZED: Simplify search query structure for better index usage
            // Prioritize full-text search, then prefix matching, then fallback to wildcard
            // PERFORMANCE: Require minimum 3 characters for wildcard searches to prevent slow queries
            $minWildcardLength = 3;
            $propertiesQuery->where(function($q) use ($searchTerm, $contentJoinAlias, $isMysql56Plus, $minWildcardLength) {
                // For search terms 3+ chars, prioritize full-text search (fastest with proper index)
                if (strlen($searchTerm) >= 3 && $isMysql56Plus) {
                    // Full-text search is most efficient - try this first
                    // Use JOIN alias to ensure proper index usage and query consistency
                    $q->whereRaw("MATCH({$contentJoinAlias}.title, {$contentJoinAlias}.address, {$contentJoinAlias}.description) AGAINST(? IN BOOLEAN MODE)", [$searchTerm]);
                } else {
                    // For shorter terms or when full-text not available, use prefix matching
                    $prefixTerm = $searchTerm . '%';
                    $q->where(function($subQ) use ($prefixTerm, $searchTerm, $contentJoinAlias, $minWildcardLength) {
                        // Prefix matching can use indexes (term%)
                        $subQ->where($contentJoinAlias . '.title', 'like', $prefixTerm)
                            ->orWhere($contentJoinAlias . '.address', 'like', $prefixTerm);
                        
                        // PERFORMANCE: Only use wildcard search if term is long enough (prevents slow index scans)
                        // Wildcard searches (%term%) cannot use indexes efficiently, so limit to 3+ characters
                        if (strlen($searchTerm) >= $minWildcardLength) {
                            // Group wildcard searches together to minimize index scan impact
                            $subQ->orWhere(function($wildcardQ) use ($searchTerm, $contentJoinAlias) {
                                $wildcardQ->where($contentJoinAlias . '.title', 'like', "%{$searchTerm}%")
                                    ->orWhere($contentJoinAlias . '.address', 'like', "%{$searchTerm}%")
                                    ->orWhere($contentJoinAlias . '.description', 'like', "%{$searchTerm}%");
                            });
                        }
                    });
                }
                
                // Search in price if numeric (separate condition to allow index usage on price)
                if (is_numeric($searchTerm)) {
                    $q->orWhere(function($priceQ) use ($searchTerm, $minWildcardLength) {
                        // Exact match first (can use index)
                        $priceQ->where('user_properties.price', $searchTerm);
                        // Only use wildcard for price if search term is long enough
                        if (strlen($searchTerm) >= $minWildcardLength) {
                            $priceQ->orWhere('user_properties.price', 'like', "%{$searchTerm}%");
                        }
                    });
                }
            });
        }

        // Ensure proper selection and distinct after content JOINs
        // OPTIMIZED: Use GROUP BY with MIN() aggregations instead of DISTINCT for better performance
        // This ensures MySQL strict mode compliance while getting the first content per property
        // PERFORMANCE: Index idx_prop_content_property_id_id on (property_id, id) optimizes MIN(id) queries
        if ($hasContentJoin) {
            // Ensure content has non-empty title and address when using JOIN
            $propertiesQuery->whereNotNull($contentJoinAlias . '.title')
                           ->where($contentJoinAlias . '.title', '!=', '')
                           ->whereNotNull($contentJoinAlias . '.address')
                           ->where($contentJoinAlias . '.address', '!=', '');
            
            // OPTIMIZED: Select content fields from JOIN to avoid eager loading
            // Use MIN() to get first content when multiple contents exist (avoids DISTINCT overhead)
            // The composite index (property_id, id) on user_property_contents makes MIN(id) queries efficient
            $propertiesQuery->select([
                'user_properties.*',
                DB::raw('MIN(' . $contentJoinAlias . '.id) as content_id'),
                DB::raw('MIN(' . $contentJoinAlias . '.title) as content_title'),
                DB::raw('MIN(' . $contentJoinAlias . '.slug) as content_slug'),
                DB::raw('MIN(' . $contentJoinAlias . '.address) as content_address'),
                DB::raw('MIN(' . $contentJoinAlias . '.description) as content_description')
            ]);
            
            // GROUP BY primary key - all user_properties columns are functionally dependent
            // MIN() aggregations ensure we get one content per property (the first one by ID)
            // Optimized by idx_prop_content_property_id_id index on user_property_contents(property_id, id)
            $propertiesQuery->groupBy('user_properties.id');
        }

        // Apply purpose filter if provided (consolidate purposes_filter and purpose)
        if ($request->has('purpose') && !empty($request->purpose)) {
            $purposeValue = $request->purpose;
            if (is_array($purposeValue)) {
                $propertiesQuery->whereIn('purpose', $purposeValue);
            } else {
                $propertiesQuery->where('purpose', $purposeValue);
            }
        } elseif ($request->has('purposes_filter') && !empty($request->purposes_filter)) {
            // Backward compatibility
            $propertiesQuery->where('purpose', $request->purposes_filter);
        }

        // Filter by employee (user_id or created_by)
        if ($request->has('employee_id') && !empty($request->employee_id)) {
            $employeeIds = is_array($request->employee_id) ? $request->employee_id : [$request->employee_id];
            // Validate that employees belong to the tenant
            $validEmployeeIds = array_intersect($employeeIds, $allowedUserIds);
            if (!empty($validEmployeeIds)) {
                $propertiesQuery->where(function($q) use ($validEmployeeIds) {
                    $q->whereIn('user_id', $validEmployeeIds)
                        ->orWhereIn('created_by', $validEmployeeIds);
                });
            }
        }

        // Filter by category_id
        if ($request->has('category_id') && !empty($request->category_id)) {
            $categoryIds = is_array($request->category_id) ? $request->category_id : [$request->category_id];
            $propertiesQuery->whereIn('category_id', $categoryIds);
        }

        // Filter by payment_method
        if ($request->has('payment_method') && !empty($request->payment_method)) {
            $propertiesQuery->where('payment_method', $request->payment_method);
        }

        // Filter by date range (created_at)
        if ($request->has('date_from') && !empty($request->date_from)) {
            try {
                $dateFrom = Carbon::parse($request->date_from)->startOfDay();
                $propertiesQuery->where('created_at', '>=', $dateFrom);
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            try {
                $dateTo = Carbon::parse($request->date_to)->endOfDay();
                $propertiesQuery->where('created_at', '<=', $dateTo);
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
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
        // Note: purpose filter is already handled above (lines 2099-2110), removed duplicate check
        if ($request->has('type') && !empty($request->type)) {
            $propertiesQuery->where('type', $request->type);
        }
        if ($request->has('beds') && !empty($request->beds)) {
            $propertiesQuery->where('beds', $request->beds);
        }
        if ($request->has('bath') && !empty($request->bath)) {
            $propertiesQuery->where('bath', $request->bath);
        }
        // OPTIMIZED: Use more efficient JSON search approach
        // For MySQL 8.0+: Use JSON_OVERLAPS for better performance when checking multiple values
        // Falls back to individual whereJsonContains for older MySQL versions
        if ($request->has('features') && !empty($request->features)) {
            $featuresArray = array_filter(array_map('trim', explode(',', $request->features)));
            if (!empty($featuresArray)) {
                // OPTIMIZED: Use cached MySQL version check
                $isMysql80Plus = DatabaseVersionService::isMysql80Plus();
                
                if ($isMysql80Plus && count($featuresArray) > 1) {
                    // MySQL 8.0+: Use JSON_OVERLAPS for better performance with multiple features
                    // This checks if the features array overlaps with any of the requested features
                    $featuresJson = json_encode($featuresArray);
                    $propertiesQuery->whereRaw(
                        "JSON_OVERLAPS(COALESCE(features, '[]'), ?)",
                        [$featuresJson]
                    );
                } else {
                    // Fallback: Use individual whereJsonContains (works on all MySQL versions)
                    // Group conditions in a closure for better query structure
                    $propertiesQuery->where(function($q) use ($featuresArray) {
                        foreach ($featuresArray as $feature) {
                            $q->whereJsonContains('features', $feature);
                        }
                    });
                }
            }
        }

        // Apply UserPropertyCharacteristic filters
        // OPTIMIZED: Use EXISTS subquery instead of JOIN to avoid DISTINCT overhead
        // EXISTS is more efficient than JOIN+DISTINCT for boolean filters
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

        // OPTIMIZED: Use EXISTS subquery instead of JOIN to avoid DISTINCT and duplicate rows
        // EXISTS is typically faster than JOIN+DISTINCT for boolean filters and doesn't multiply rows
        if ($hasCharacteristicFilter) {
            $propertiesQuery->whereExists(function ($query) use ($activeFilters) {
                $query->select(DB::raw(1))
                    ->from('user_property_characteristics as upc')
                    ->whereColumn('upc.property_id', 'user_properties.id');
                
                foreach ($activeFilters as $filter => $value) {
                    $query->where("upc.{$filter}", $value);
                }
            });
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

        // OPTIMIZED: Make pagination configurable with max limit to prevent abuse
        // Increased default from 10 to 20 for better UX and fewer requests
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        
        // OPTIMIZED: Support simple pagination to skip COUNT query for better performance
        $useSimplePagination = filter_var($request->input('simple_pagination', false), FILTER_VALIDATE_BOOLEAN);
        
        // OPTIMIZED: Cache query results to improve performance
        // Cache key includes owner ID and hash of filters/pagination for uniqueness
        // OPTIMIZED: Use serialize() instead of json_encode() for better performance with arrays
        // Sort filters to ensure consistent cache keys regardless of parameter order
        $filters = $request->except(['page', 'per_page', 'include_views', 'include_filters', 'simple_pagination']);
        ksort($filters); // Sort by key for consistent hashing
        $cacheKey = 'properties_list_' . $ownerId . '_' . md5(serialize([
            'filters' => $filters,
            'page' => $request->input('page', 1),
            'per_page' => $perPage,
            'simple_pagination' => $useSimplePagination
        ]));
        
        // Cache for 5 minutes (shorter TTL for first page, can be adjusted)
        $cacheTTL = $request->input('page', 1) == 1 ? 300 : 600; // 5 min for page 1, 10 min for others
        
        // OPTIMIZED: Cache pagination COUNT separately to avoid executing on every request
        $totalCountCacheKey = $cacheKey . '_total';
        $totalCount = null;
        
        // OPTIMIZED: Add cache stampede protection using locks
        // Prevents multiple requests from regenerating cache simultaneously when cache expires
        $lockKey = 'lock_' . $cacheKey;
        
        // PERFORMANCE: Track cache hits/misses for monitoring
        $cacheStats = ['hits' => 0, 'misses' => 0];
        
        // Helper function to get or set cache with stampede protection
        $getOrSetCache = function ($key, $ttl, $callback) use ($lockKey, &$cacheStats) {
            // Try to get from cache first
            $cached = Cache::get($key);
            if ($cached !== null) {
                $cacheStats['hits']++;
                return $cached;
            }
            
            $cacheStats['misses']++;
            
            // Cache miss - use lock to prevent stampede
            $lock = Cache::lock($lockKey, 10); // 10 second lock timeout
            try {
                if ($lock->get(3)) { // Wait up to 3 seconds for lock
                    try {
                        // Double-check cache after acquiring lock (another request may have populated it)
                        $cached = Cache::get($key);
                        if ($cached !== null) {
                            $cacheStats['hits']++;
                            $cacheStats['misses']--; // Adjust stats
                            return $cached;
                        }
                        
                        // Generate and cache the value
                        $queryStartTime = microtime(true);
                        $value = $callback();
                        $queryTime = (microtime(true) - $queryStartTime) * 1000; // Convert to milliseconds
                        
                        Cache::put($key, $value, $ttl);
                        
                        // PERFORMANCE: Log slow queries (>500ms) for monitoring
                        if ($queryTime > 500) {
                            Log::warning('Slow property query detected', [
                                'cache_key' => $key,
                                'query_time_ms' => round($queryTime, 2),
                                'threshold_ms' => 500
                            ]);
                        }
                        
                        return $value;
                    } finally {
                        $lock->release();
                    }
                } else {
                    // Could not acquire lock - wait briefly and check cache again
                    usleep(200000); // 200ms
                    $cached = Cache::get($key);
                    if ($cached !== null) {
                        $cacheStats['hits']++;
                        $cacheStats['misses']--; // Adjust stats
                        return $cached;
                    }
                    return $callback();
                }
            } catch (\Exception $e) {
                // If lock fails, fall back to direct callback
                Log::warning('Cache lock failed in PropertyController::index', ['error' => $e->getMessage()]);
                return $callback();
            }
        };
        
        if ($useSimplePagination) {
            // Simple pagination skips COUNT query for better performance
            $properties = $getOrSetCache($cacheKey, $cacheTTL, function () use ($propertiesQuery, $perPage) {
                return $propertiesQuery->simplePaginate($perPage);
            });
        } else {
            // Full pagination with COUNT query
            $properties = $getOrSetCache($cacheKey, $cacheTTL, function () use ($propertiesQuery, $perPage) {
                return $propertiesQuery->paginate($perPage);
            });
            
            // Cache the total count separately to avoid re-executing COUNT on cached results
            $totalCount = $getOrSetCache($totalCountCacheKey, $cacheTTL, function () use ($propertiesQuery) {
                return $propertiesQuery->count();
            });
        }

        // === GA4: views per property (last 30 days by default) ===
        // OPTIMIZED: Make GA query optional via include_views parameter to improve response time
        $viewsBySlug = [];
        $includeViews = filter_var($request->input('include_views', true), FILTER_VALIDATE_BOOLEAN);
        
        if ($includeViews) {
            $tenantId  = $owner->username;                     // align GA context with tenant owner
            $days      = (int) $request->input('days', 30);   // override with ?days=7 if you want
            $startDate = Carbon::now()->subDays($days);
            $endDate   = Carbon::now();

            // Collect slugs for the current page
            // FIX: Handle both JOIN and eager loaded content scenarios
            $slugs = $properties->getCollection()
                ->map(function ($p) use ($hasContentJoin) {
                    // If content JOIN was used, get slug from JOIN data
                    if ($hasContentJoin && isset($p->content_slug)) {
                        return $p->content_slug;
                    }
                    // Otherwise, use eager loaded relationship
                    return optional($p->contents->first())->slug;
                })
                ->filter() // Remove null/empty values
                ->values();

            // Build candidate paths for each slug (adjust prefixes to match your frontend routes)
            $paths = [];
            foreach ($slugs as $slug) {
                $paths[] = "/property/{$slug}";
                $paths[] = "/ar/property/{$slug}";
                $paths[] = "/en/property/{$slug}";
            }

            // OPTIMIZED: Query GA4 with non-blocking cache - returns cached data immediately
            // If cache miss, returns empty array to avoid blocking response
            // Cache will be populated by background job or next request
            if (!empty($paths)) {
                $cacheKey = "ga_views_{$tenantId}_{$days}_" . md5(implode(',', $slugs->toArray()));
                
                // Try to get from cache first (non-blocking)
                $viewsBySlug = Cache::get($cacheKey, []);
                
                // DEBUG: Log if no slugs collected or cache miss
                if ($slugs->isEmpty()) {
                    Log::warning('No slugs collected for GA views', [
                        'owner_id' => $ownerId,
                        'tenant_id' => $tenantId,
                        'has_content_join' => $hasContentJoin,
                        'properties_count' => $properties->count()
                    ]);
                }
                
                // If cache miss, dispatch background job to fetch and cache data
                // This prevents blocking the response while still populating cache for future requests
                if (empty($viewsBySlug)) {
                    // Dispatch job to fetch GA data in background (non-blocking)
                    // The job will populate the cache for future requests
                    try {
                        \App\Jobs\FetchGoogleAnalyticsViews::dispatch($cacheKey, $startDate, $endDate, $paths, $tenantId, $slugs->toArray())
                            ->onQueue('default');
                        
                        // DEBUG: Log job dispatch for troubleshooting
                        Log::debug('GA views job dispatched', [
                            'cache_key' => $cacheKey,
                            'slugs_count' => $slugs->count(),
                            'paths_count' => count($paths),
                            'tenant_id' => $tenantId
                        ]);
                    } catch (\Exception $e) {
                        // If job dispatch fails, log and continue with empty views
                        Log::warning('Failed to dispatch GA views job', [
                            'error' => $e->getMessage(),
                            'cache_key' => $cacheKey,
                            'tenant_id' => $tenantId
                        ]);
                    }
                } else {
                    // DEBUG: Log cache hit for monitoring
                    Log::debug('GA views cache hit', [
                        'cache_key' => $cacheKey,
                        'views_count' => count($viewsBySlug),
                        'tenant_id' => $tenantId
                    ]);
                }
            } else {
                // DEBUG: Log when no paths are built
                Log::debug('No paths built for GA views', [
                    'slugs_count' => $slugs->count(),
                    'tenant_id' => $tenantId
                ]);
            }
        }

        // ===== Get filter options (CACHED - 1 hour) =====
        // OPTIMIZED: Make filter options optional via include_filters parameter to reduce payload size
        $includeFilters = filter_var($request->input('include_filters', true), FILTER_VALIDATE_BOOLEAN);
        $availablePurposes = [];
        $priceRange = ['min' => 0, 'max' => 0];
        $areaRange = ['min' => 0];
        $availableTypes = [];
        $availableBeds = [];
        $availableBath = [];
        $availableFeatures = [];
        $characteristicFilterOptions = [];
        $employees = [];
        $categories = [];
        $paymentMethods = [];
        $dateRange = ['min' => null, 'max' => null];
        
        if ($includeFilters) {
            $cacheKey = "property_filter_options_{$ownerId}";
            // OPTIMIZED: Use service to generate filter options (reusable for cache pre-warming)
            $filterOptions = Cache::remember($cacheKey, 3600, function () use ($allowedUserIds) {
                return PropertyFilterOptionsService::generateFilterOptions($allowedUserIds);
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
            $employees = $filterOptions['employees'] ?? [];
            $categories = $filterOptions['categories'] ?? [];
            $paymentMethods = $filterOptions['payment_methods'] ?? [];
            $dateRange = $filterOptions['date_range'] ?? ['min' => null, 'max' => null];
        }

        $specificsFilters = [
            'price_range' => $priceRange,
            'area_range' => $areaRange,
            'purpose' => $availablePurposes,
            'type' => $availableTypes,
            'beds' => $availableBeds,
            'bath' => $availableBath,
            'features' => array_values($availableFeatures),
            'characteristics' => $characteristicFilterOptions,
            'employees' => $employees,
            'categories' => $categories,
            'payment_methods' => $paymentMethods,
            'date_range' => $dateRange,
        ];

        // === Format response ===
        // OPTIMIZED: Support field selection via ?fields parameter to reduce payload size
        // Example: ?fields=id,title,price,area
        $requestedFields = $request->input('fields');
        $allowedFields = [
            'id', 'visits', 'title', 'address', 'slug', 'price', 'type', 'beds', 'bath',
            'area', 'transaction_type', 'features', 'status', 'featured_image', 'featured',
            'show_reservations', 'created_at', 'updated_at', 'payment_method', 'creator'
        ];
        
        $fieldsToInclude = null;
        if ($requestedFields) {
            $requestedFieldsArray = array_map('trim', explode(',', $requestedFields));
            $fieldsToInclude = array_intersect($requestedFieldsArray, $allowedFields);
            // Always include 'id' as it's required for identification
            if (!in_array('id', $fieldsToInclude)) {
                $fieldsToInclude[] = 'id';
            }
        }
        
        // OPTIMIZED: Use content from JOIN if available, otherwise use eager loaded relationship
        // Filter out properties that don't have valid title and address to prevent "No Title"/"No Address" fallbacks
        // Use ->values() after ->filter() to re-index the collection and maintain array structure (not object with keys)
        $formattedProperties = $properties->getCollection()->filter(function ($property) use ($hasContentJoin) {
            if ($hasContentJoin && isset($property->content_slug)) {
                // When using JOIN, check if content from JOIN has valid title and address
                $hasTitle = !empty($property->content_title ?? null);
                $hasAddress = !empty($property->content_address ?? null);
                return $hasTitle && $hasAddress;
            } else {
                // When using eager loading, check if first content has valid title and address
                $content = optional($property->contents->first());
                $hasTitle = !empty($content->title ?? null);
                $hasAddress = !empty($content->address ?? null);
                return $hasTitle && $hasAddress;
            }
        })->values()->map(function ($property) use ($viewsBySlug, $fieldsToInclude, $hasContentJoin) {
            // Use content from JOIN if available (when filtering by city/district/search)
            if ($hasContentJoin && isset($property->content_slug)) {
                $content = (object) [
                    'title' => $property->content_title ?? null,
                    'slug' => $property->content_slug ?? null,
                    'address' => $property->content_address ?? null,
                    'description' => $property->content_description ?? null,
                ];
            } else {
                // Fallback to eager loaded relationship
                $content = optional($property->contents->first());
            }
            $slug = $content->slug ?? null;

            $propertyData = [
                'id'               => $property->id,
                'visits'           => (int) ($viewsBySlug[$slug] ?? 0),
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
            
            // Filter fields if field selection is requested
            if ($fieldsToInclude !== null) {
                return array_intersect_key($propertyData, array_flip($fieldsToInclude));
            }
            
            return $propertyData;
        });

        // OPTIMIZED: Combine count queries into single query for better performance
        // Cache combined counts for 5 minutes
        $cacheKey = "property_counts_{$ownerId}";
        $counts = Cache::remember($cacheKey, 300, function () use ($allowedUserIds) {
            return Property::whereIn('user_id', $allowedUserIds)
                ->selectRaw('
                    SUM(CASE WHEN featured = 1 AND reorder_featured > 0 THEN 1 ELSE 0 END) as total_reorder_featured,
                    SUM(CASE WHEN completion_status != "complete" OR completion_status IS NULL THEN 1 ELSE 0 END) as incomplete_count,
                    SUM(CASE WHEN completion_status = "complete" THEN 1 ELSE 0 END) as completed_count
                ')
                ->first();
        });
        
        $totalReorderFeatured = (int) ($counts->total_reorder_featured ?? 0);
        $incompleteCount = (int) ($counts->incomplete_count ?? 0);
        $completedCount = (int) ($counts->completed_count ?? 0);

        // Build pagination metadata
        $paginationData = [
            'per_page'     => $properties->perPage(),
            'current_page' => $properties->currentPage(),
            'from'         => $properties->firstItem(),
            'to'           => $properties->lastItem(),
        ];
        
        if ($useSimplePagination) {
            // Simple pagination: only include has_more_pages indicator
            $paginationData['has_more_pages'] = $properties->hasMorePages();
            if ($properties->hasMorePages()) {
                $paginationData['next_page_url'] = $properties->nextPageUrl();
            }
        } else {
            // Full pagination: include total count and last page
            $paginationData['total'] = $totalCount ?? $properties->total();
            $paginationData['last_page'] = (int) ceil(($totalCount ?? $properties->total()) / $properties->perPage());
        }
        
        // Build response data
        $responseData = [
            'properties' => $formattedProperties,
            'purposes_filter' => $availablePurposes,
            'specifics_filters' => $specificsFilters,
            'total_reorder_featured' => $totalReorderFeatured,
            'incomplete_count' => $incompleteCount,
            'completed_count' => $completedCount,
            'pagination' => $paginationData
        ];
        
        // PERFORMANCE: Log performance metrics for monitoring
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $queryEndCount = DB::getQueryLog() ? count(DB::getQueryLog()) : 0;
        $queryCount = $queryEndCount - $queryStartCount;
        $cacheHitRate = ($cacheStats['hits'] + $cacheStats['misses']) > 0 
            ? round(($cacheStats['hits'] / ($cacheStats['hits'] + $cacheStats['misses'])) * 100, 2) 
            : 0;
        
        // Log slow requests (>1000ms) or cache misses for monitoring
        if ($totalTime > 1000 || $cacheStats['misses'] > 0) {
            Log::info('PropertyController::index performance metrics', [
                'total_time_ms' => round($totalTime, 2),
                'query_count' => $queryCount,
                'cache_hits' => $cacheStats['hits'],
                'cache_misses' => $cacheStats['misses'],
                'cache_hit_rate_percent' => $cacheHitRate,
                'owner_id' => $ownerId,
                'page' => $request->input('page', 1),
                'per_page' => $perPage,
                'has_filters' => $request->except(['page', 'per_page', 'include_views', 'include_filters', 'simple_pagination']) !== [],
                'slow_request' => $totalTime > 1000
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'data' => $responseData
        ], 200);
    }

    /**
     * Get filter options for properties
     * Returns all available filter options for frontend dropdowns and filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function filterOptions(Request $request): JsonResponse
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

        // Cache filter options (1 hour TTL)
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

            // OPTIMIZED: Get UserPropertyCharacteristic filter options
            $propertyIds = Property::whereIn('user_id', $allowedUserIds)
                ->pluck('id');

            $characteristicFilterOptions = [];
            $characteristicFields = [
                'private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
                'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
                'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 'floors',
                'bathrooms', 'rooms', 'building_age'
            ];

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

            // OPTIMIZED: Get employees who have created or own properties using single UNION query
            // This reduces from 2 separate queries to 1 query
            $employeeIds = DB::table('user_properties')
                ->whereIn('user_id', $allowedUserIds)
                ->select('user_id as employee_id')
                ->distinct()
                ->union(
                    DB::table('user_properties')
                        ->whereIn('user_id', $allowedUserIds)
                        ->whereNotNull('created_by')
                        ->select('created_by as employee_id')
                        ->distinct()
                )
                ->pluck('employee_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            // Filter to only include employees that are in allowedUserIds
            $employeeIds = array_intersect($employeeIds, $allowedUserIds);

            $employees = \App\Models\User::whereIn('id', $employeeIds)
                ->select('id', 'first_name', 'last_name', 'email', 'username')
                ->get()
                ->map(function($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: ($emp->username ?? $emp->email),
                        'email' => $emp->email,
                    ];
                })
                ->values()
                ->toArray();

            // Get categories used in properties
            $categoryIds = Property::whereIn('user_id', $allowedUserIds)
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $categories = ApiUserCategory::whereIn('id', $categoryIds)
                ->select('id', 'name')
                ->get()
                ->map(function($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ];
                })
                ->values()
                ->toArray();

            // Get distinct payment methods
            $paymentMethods = Property::whereIn('user_id', $allowedUserIds)
                ->whereNotNull('payment_method')
                ->where('payment_method', '!=', '')
                ->distinct()
                ->pluck('payment_method')
                ->filter()
                ->sort()
                ->values()
                ->toArray();

            // Get date range (min/max created_at)
            $dateStats = Property::whereIn('user_id', $allowedUserIds)
                ->whereNotNull('created_at')
                ->selectRaw('MIN(created_at) as min_date, MAX(created_at) as max_date')
                ->first();

            $dateRange = [
                'min' => $dateStats && $dateStats->min_date ? Carbon::parse($dateStats->min_date)->format('Y-m-d') : null,
                'max' => $dateStats && $dateStats->max_date ? Carbon::parse($dateStats->max_date)->format('Y-m-d') : null,
            ];

            return [
                'purposes' => $availablePurposes,
                'price_range' => $priceRange,
                'area_range' => $areaRange,
                'types' => $availableTypes,
                'beds' => $availableBeds,
                'bath' => $availableBath,
                'features' => $availableFeatures,
                'characteristics' => $characteristicFilterOptions,
                'employees' => $employees,
                'categories' => $categories,
                'payment_methods' => $paymentMethods,
                'date_range' => $dateRange,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $filterOptions,
        ], 200);
    }

    /**
     * Get property statistics cards for the authenticated tenant
     * Returns counts for properties for sale, for rent, and total
     * Works for both tenant owners and their employees
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cards(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                ], 401);
            }

            // Resolve tenant owner and include all employees under that tenant
            // This ensures employees see the same statistics as the tenant owner
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
            $ownerId = (int) $owner->id;

            // Cache the final results for 5 minutes (300 seconds)
            // This significantly reduces database load for frequently accessed statistics
            $cacheKey = "property_cards_{$ownerId}";
            $result = Cache::remember($cacheKey, 300, function () use ($ownerId) {
                $allowedUserIds = [$ownerId];
                try {
                    $employeeCacheKey = "tenant_employees_{$ownerId}";
                    $employeeIds = Cache::remember($employeeCacheKey, 300, function () use ($ownerId) {
                        return \App\Models\User::where('tenant_id', $ownerId)
                            ->where('account_type', 'employee')
                            ->pluck('id')
                            ->toArray();
                    });
                    $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
                } catch (\Throwable $e) {
                    // Continue with owner only if employee fetch fails
                }

                // Single aggregated query instead of three separate queries
                // This reduces database round trips and improves performance by ~60-70%
                // Uses composite index: user_properties_user_purpose_completion_index
                $stats = Property::whereIn('user_id', $allowedUserIds)
                    ->where('completion_status', 'complete')
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN purpose = ? THEN 1 ELSE 0 END) as for_sale,
                        SUM(CASE WHEN purpose = ? THEN 1 ELSE 0 END) as for_rent
                    ', ['sale', 'rent'])
                    ->first();

                // Get incomplete stats: total, for_sale, for_rent (same completion filter as listDrafts)
                $incompleteStats = Property::whereIn('user_id', $allowedUserIds)
                    ->where(function($query) {
                        $query->where('completion_status', '!=', 'complete')
                              ->orWhereNull('completion_status');
                    })
                    ->selectRaw('
                        COUNT(*) as total,
                        SUM(CASE WHEN purpose = ? THEN 1 ELSE 0 END) as for_sale,
                        SUM(CASE WHEN purpose = ? THEN 1 ELSE 0 END) as for_rent
                    ', ['sale', 'rent'])
                    ->first();

                return [
                    'complete' => [
                        'for_sale' => (int) ($stats->for_sale ?? 0),
                        'for_rent' => (int) ($stats->for_rent ?? 0),
                    ],
                    'incomplete' => [
                        'for_sale' => (int) ($incompleteStats->for_sale ?? 0),
                        'for_rent' => (int) ($incompleteStats->for_rent ?? 0),
                    ],
                    'total' => (int) (($stats->total ?? 0) + ($incompleteStats->total ?? 0)),
                    'complete_count' => (int) ($stats->total ?? 0),
                    'incomplete_count' => (int) ($incompleteStats->total ?? 0),
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching property cards', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch property statistics',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
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
            $query = Property::with([
                'project.contents', 
                'building' => function($q) {
                    $q->select('id', 'name');
                }, 
                'contents'
            ])
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

                // Safely get building name - handle case where building might not be loaded or is null
                $buildingName = 'N/A';
                try {
                    if ($property->relationLoaded('building') && $property->building) {
                        // Building relationship is loaded
                        if (is_object($property->building) && property_exists($property->building, 'name')) {
                            $buildingName = $property->building->name ?? 'N/A';
                        }
                    } elseif ($property->building_id) {
                        // Try to access building if not loaded (lazy load)
                        $building = $property->building;
                        if ($building && is_object($building) && property_exists($building, 'name')) {
                            $buildingName = $building->name ?? 'N/A';
                        }
                    }
                } catch (\Exception $e) {
                    // If any error accessing building, just use default
                    $buildingName = 'N/A';
                }

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

    /**
     * Export properties to CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
    {
        try {
            // Helper function to convert IDs to int array (supports array, comma-separated string, or single value)
            $toIntArray = function ($v): array {
                if (is_null($v) || $v === '') return [];
                if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
                if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
                if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
                return [];
            };

            // Get property IDs if provided
            $propertyIds = $toIntArray($request->input('ids'));

            // Validate: Either ids OR date range must be provided
            if (empty($propertyIds)) {
                // If no IDs, date range is required
                $request->validate([
                    'date_from' => 'required|date',
                    'date_to' => 'required|date|after_or_equal:date_from',
                ], [
                    'date_from.required' => 'Either ids parameter or date_from parameter is required. Please provide a date range or specific property IDs to export.',
                    'date_to.required' => 'Either ids parameter or date_to parameter is required. Please provide a date range or specific property IDs to export.',
                    'date_to.after_or_equal' => 'The date_to must be after or equal to date_from. Please ensure your end date is not before your start date.',
                    'date_from.date' => 'The date_from must be a valid date in YYYY-MM-DD format.',
                    'date_to.date' => 'The date_to must be a valid date in YYYY-MM-DD format.',
                ]);
            } else {
                // If IDs provided, validate them and make date range optional
                $request->validate([
                    'ids' => 'required',
                    'date_from' => 'nullable|date',
                    'date_to' => 'nullable|date|after_or_equal:date_from',
                ], [
                    'ids.required' => 'The ids parameter is required when provided.',
                    'date_from.date' => 'The date_from must be a valid date in YYYY-MM-DD format.',
                    'date_to.date' => 'The date_to must be a valid date in YYYY-MM-DD format.',
                    'date_to.after_or_equal' => 'The date_to must be after or equal to date_from.',
                ]);
            }

            $user = $request->user();

            // Check permission
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_PERMISSION_DENIED',
                    'message' => 'Authentication required to export properties',
                    'timestamp' => now()->toIso8601String(),
                ], 401);
            }

            // Resolve tenant owner (same logic as index method)
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
            } catch (\Throwable $e) {
                // Continue with owner only if employee fetch fails
            }

            // Collect filters from request
            $filters = $request->only([
                'date_from',
                'date_to',
                'purpose',
                'purposes_filter',
                'type',
                'price_from',
                'price_to',
                'area_from',
                'area_to',
                'beds',
                'bath',
                'category_id',
                'status',
                'featured',
                'city_id',
                'district_id',
                'search',
                'features',
            ]);

            // Add property IDs to filters
            $filters['ids'] = $propertyIds;

            // Check if any properties exist matching the criteria
            $availableIds = $this->getAvailablePropertyIds($filters, $ownerId, $allowedUserIds);
            if (empty($availableIds)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_NO_PROPERTIES',
                    'message' => 'No properties found matching the specified criteria',
                    'details' => [
                        'user_id' => $ownerId,
                        'filters_applied' => $filters,
                        'suggestion' => 'Try adjusting your filters (date range, purpose, type, etc.) or check if you have any available properties.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Get format (default: 'xlsx')
            $format = $request->input('format', 'xlsx');
            if (!in_array($format, ['xlsx', 'csv'])) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_VALIDATION_ERROR',
                    'message' => 'Invalid export format specified',
                    'errors' => [
                        'format' => ['The format must be either "xlsx" or "csv".'],
                    ],
                    'details' => [
                        'provided_format' => $format,
                        'allowed_formats' => ['xlsx', 'csv'],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Generate filename with timestamp
            $filename = 'properties_export_' . now()->format('Y-m-d_His');

            // Create export instance
            $export = new \App\Exports\PropertiesExport($ownerId, $allowedUserIds, $filters);

            // Return file download based on format
            try {
                if ($format === 'csv') {
                    return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
                }

                return Excel::download($export, $filename . '.xlsx');
            } catch (\Exception $e) {
                Log::error('Properties export file generation error', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'filters' => $filters,
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_FILE_GENERATION_ERROR',
                    'message' => 'Failed to generate export file',
                    'details' => [
                        'user_id' => auth()->id(),
                        'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while generating the export file. Please try again.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'EXPORT_VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'details' => [
                    'user_id' => auth()->id(),
                ],
                'timestamp' => now()->toIso8601String(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Properties export error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 'EXPORT_SYSTEM_ERROR',
                'message' => 'Failed to export properties',
                'details' => [
                    'user_id' => auth()->id(),
                    'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Export properties in import-ready format (optimized for bulk updates)
     * This export includes only importable columns plus the 'id' column for updates
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function exportForImport(Request $request)
    {
        try {
            // Helper function to convert IDs to int array
            $toIntArray = function ($v): array {
                if (is_null($v) || $v === '') return [];
                if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
                if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
                if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
                return [];
            };

            // Get property IDs if provided
            $propertyIds = $toIntArray($request->input('ids'));

            // Validate: Either ids OR date range must be provided
            if (empty($propertyIds)) {
                $request->validate([
                    'date_from' => 'required|date',
                    'date_to' => 'required|date|after_or_equal:date_from',
                ], [
                    'date_from.required' => 'Either ids parameter or date_from parameter is required.',
                    'date_to.required' => 'Either ids parameter or date_to parameter is required.',
                    'date_to.after_or_equal' => 'The date_to must be after or equal to date_from.',
                    'date_from.date' => 'The date_from must be a valid date in YYYY-MM-DD format.',
                    'date_to.date' => 'The date_to must be a valid date in YYYY-MM-DD format.',
                ]);
            } else {
                $request->validate([
                    'ids' => 'required',
                    'date_from' => 'nullable|date',
                    'date_to' => 'nullable|date|after_or_equal:date_from',
                ], [
                    'ids.required' => 'The ids parameter is required when provided.',
                    'date_from.date' => 'The date_from must be a valid date in YYYY-MM-DD format.',
                    'date_to.date' => 'The date_to must be a valid date in YYYY-MM-DD format.',
                    'date_to.after_or_equal' => 'The date_to must be after or equal to date_from.',
                ]);
            }

            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_PERMISSION_DENIED',
                    'message' => 'Authentication required to export properties',
                    'timestamp' => now()->toIso8601String(),
                ], 401);
            }

            // Resolve tenant owner
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
            } catch (\Throwable $e) {
                // Continue with owner only if employee fetch fails
            }

            // Collect filters from request
            $filters = $request->only([
                'date_from',
                'date_to',
                'purpose',
                'purposes_filter',
                'type',
                'price_from',
                'price_to',
                'area_from',
                'area_to',
                'beds',
                'bath',
                'category_id',
                'status',
                'featured',
                'city_id',
                'district_id',
                'search',
                'features',
            ]);

            // Add property IDs to filters
            $filters['ids'] = $propertyIds;

            // Check if any properties exist matching the criteria
            $availableIds = $this->getAvailablePropertyIds($filters, $ownerId, $allowedUserIds);
            if (empty($availableIds)) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_NO_PROPERTIES',
                    'message' => 'No properties found matching the specified criteria',
                    'details' => [
                        'user_id' => $ownerId,
                        'filters_applied' => $filters,
                        'suggestion' => 'Try adjusting your filters or check if you have any available properties.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Get format (default: 'xlsx')
            $format = $request->input('format', 'xlsx');
            if (!in_array($format, ['xlsx', 'csv'])) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_VALIDATION_ERROR',
                    'message' => 'Invalid export format specified',
                    'errors' => [
                        'format' => ['The format must be either "xlsx" or "csv".'],
                    ],
                    'details' => [
                        'provided_format' => $format,
                        'allowed_formats' => ['xlsx', 'csv'],
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Generate filename with timestamp
            $filename = 'properties_import_ready_' . now()->format('Y-m-d_His');

            // Create import-ready export instance
            $export = new \App\Exports\PropertiesImportReadyExport($ownerId, $allowedUserIds, $filters);

            // Return file download based on format
            try {
                if ($format === 'csv') {
                    return Excel::download($export, $filename . '.csv', \Maatwebsite\Excel\Excel::CSV);
                }

                return Excel::download($export, $filename . '.xlsx');
            } catch (\Exception $e) {
                Log::error('Properties import-ready export file generation error', [
                    'user_id' => auth()->id(),
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'filters' => $filters,
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'EXPORT_FILE_GENERATION_ERROR',
                    'message' => 'Failed to generate import-ready export file',
                    'details' => [
                        'user_id' => auth()->id(),
                        'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while generating the export file. Please try again.',
                    ],
                    'timestamp' => now()->toIso8601String(),
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'EXPORT_VALIDATION_ERROR',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'details' => [
                    'user_id' => auth()->id(),
                ],
                'timestamp' => now()->toIso8601String(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Properties import-ready export error', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'code' => 'EXPORT_SYSTEM_ERROR',
                'message' => 'Failed to export properties for import',
                'details' => [
                    'user_id' => auth()->id(),
                    'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred. Please try again later.',
                ],
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Get available property IDs based on filters
     * 
     * Returns array of property IDs that are:
     * - Owned by the authenticated user/tenant
     * - Have property_status = 'available' or null (not 'rented')
     * - Optionally: without active rentals (if rentals relationship exists)
     * - Respects all the same filters as the export query
     * 
     * @param array $filters Optional filters (date_from, date_to, purpose, type, status, etc.)
     * @param int|null $ownerId Optional owner ID (defaults to authenticated user's tenant owner)
     * @param array|null $allowedUserIds Optional array of allowed user IDs (defaults to owner + employees)
     * @return array Array of property IDs
     */
    protected function getAvailablePropertyIds(array $filters = [], ?int $ownerId = null, ?array $allowedUserIds = null): array
    {
        $user = auth()->user();
        
        // Resolve tenant owner if not provided
        if ($ownerId === null) {
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
            $ownerId = (int) $owner->id;
        }

        // Get allowed user IDs if not provided
        if ($allowedUserIds === null) {
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
            } catch (\Throwable $e) {
                // Continue with owner only if employee fetch fails
            }
        }

        $query = Property::query()
            ->whereIn('user_id', $allowedUserIds)
            ->where(function ($q) {
                $q->whereNull('property_status')
                  ->orWhere('property_status', 'available')
                  ->orWhere('property_status', '!=', 'rented');
            });

        // Apply property IDs filter if provided
        if (!empty($filters['ids']) && is_array($filters['ids']) && count($filters['ids']) > 0) {
            $query->whereIn('id', $filters['ids']);
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply purpose filter
        if (!empty($filters['purposes_filter'])) {
            $query->where('purpose', $filters['purposes_filter']);
        }
        if (!empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }

        // Apply type filter
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply price filters
        if (!empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }
        if (!empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        // Apply area filters
        if (!empty($filters['area_from'])) {
            $query->where('area', '>=', $filters['area_from']);
        }
        if (!empty($filters['area_to'])) {
            $query->where('area', '<=', $filters['area_to']);
        }

        // Apply beds filter
        if (!empty($filters['beds'])) {
            $query->where('beds', $filters['beds']);
        }

        // Apply bath filter
        if (!empty($filters['bath'])) {
            $query->where('bath', $filters['bath']);
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // Apply featured filter
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $query->where('featured', $filters['featured']);
        }

        // Apply city filter
        if (!empty($filters['city_id'])) {
            $query->whereHas('contents', function ($q) use ($filters) {
                $q->where('city_id', $filters['city_id']);
            });
        }

        // Apply district filter
        if (!empty($filters['district_id'])) {
            $query->whereHas('contents', function ($q) use ($filters) {
                $q->where('state_id', $filters['district_id']);
            });
        }

        // Apply search filter (title/address)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('contents', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Apply features filter
        if (!empty($filters['features'])) {
            $featuresArray = explode(',', $filters['features']);
            foreach ($featuresArray as $feature) {
                $feature = trim($feature);
                $query->whereJsonContains('features', $feature);
            }
        }

        // Optionally filter out properties with active rentals
        if (method_exists(Property::class, 'rentals')) {
            $query->whereDoesntHave('rentals', function ($q) use ($ownerId) {
                $q->where('user_id', $ownerId)
                  ->whereIn('status', ['active', 'draft']);
            });
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * List incomplete/draft properties
     * GET /api/properties/drafts
     */
    public function listDrafts(Request $request)
    {
        try {
            $user = $request->user();
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

            $query = Property::with(['contents:id,property_id,title,address'])
                ->whereIn('user_id', $allowedUserIds)
                ->where(function($q) {
                    $q->where('completion_status', '!=', 'complete')
                      ->orWhereNull('completion_status');
                });

            // Search by title or address
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = trim($request->search);
                $query->whereHas('contents', function($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                      ->orWhere('address', 'like', "%{$searchTerm}%");
                });
            }

            // Filter by import batch
            if ($request->has('import_batch_id') && !empty($request->import_batch_id)) {
                $query->where('import_batch_id', $request->import_batch_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $drafts = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $drafts->items(),
                'pagination' => [
                    'current_page' => $drafts->currentPage(),
                    'last_page' => $drafts->lastPage(),
                    'per_page' => $drafts->perPage(),
                    'total' => $drafts->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error listing drafts: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to list draft properties',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show draft property details
     * GET /api/properties/drafts/{id}
     */
    public function showDraft($id)
    {
        try {
            $user = auth()->user();
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

            $property = Property::with([
                'contents',
                'galleryImages',
                'proertyAmenities.amenity',
                'specifications',
                'UserPropertyCharacteristics',
                'category',
            ])
                ->where('id', $id)
                ->where('user_id', $owner->id)
                ->where('completion_status', 'incomplete')
                ->first();

            if (!$property) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Draft property not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $property,
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing draft: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve draft property',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update draft property (partial completion)
     * PATCH /api/properties/drafts/{id}
     */
    public function updateDraft(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

            $property = Property::where('id', $id)
                ->where('user_id', $owner->id)
                ->where('completion_status', 'incomplete')
                ->first();

            if (!$property) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Draft property not found',
                ], 404);
            }

            $defaultLanguage = Language::where('user_id', $owner->id)
                ->where('is_default', 1)
                ->firstOrFail();

            DB::transaction(function () use ($property, $request, $owner, $defaultLanguage) {
                // Update property fields
                $propertyData = [];
                $allowedFields = ['price', 'pricePerMeter', 'purpose', 'type', 'beds', 'bath', 'area', 
                    'size', 'video_url', 'virtual_tour', 'features', 'payment_method', 
                    'water_meter_number', 'electricity_meter_number', 'deed_number', 
                    'advertising_license', 'latitude', 'longitude', 'category_id', 'project_id', 'building_id'];

                foreach ($allowedFields as $field) {
                    if ($request->has($field)) {
                        $propertyData[$field] = $request->input($field);
                    }
                }

                if (!empty($propertyData)) {
                    $property->update($propertyData);
                }

                // Update PropertyContent if provided
                if ($request->has('title') || $request->has('address') || $request->has('description')) {
                    $contentData = [];
                    if ($request->has('title')) $contentData['title'] = $request->title;
                    if ($request->has('address')) $contentData['address'] = $request->address;
                    if ($request->has('description')) {
                        $contentData['description'] = $request->description;
                        $contentData['meta_description'] = Str::limit($request->description, 150);
                    }

                    $existingContent = PropertyContent::where('property_id', $property->id)
                        ->where('language_id', $defaultLanguage->id)
                        ->first();

                    if ($existingContent) {
                        // Ensure slug is never accepted from request
                        unset($contentData['slug']);
                        // Regenerate slug if title is being updated
                        if (isset($contentData['title'])) {
                            $contentData['slug'] = PropertyContent::generateUniqueSlug($contentData['title'], $property->id);
                        }
                        $existingContent->update($contentData);
                    } else {
                        $contentData['language_id'] = $defaultLanguage->id;
                        $contentData['category_id'] = $property->category_id;
                        PropertyContent::storePropertyContent($owner->id, $property->id, $contentData);
                    }
                }

                // Recalculate missing fields
                $requiredFields = ['title', 'price', 'address', 'description', 'purpose', 'type', 'area'];
                $missing = [];
                
                // Get current property data
                $currentData = [
                    'title' => $property->contents()->where('language_id', $defaultLanguage->id)->value('title'),
                    'price' => $property->price,
                    'address' => $property->contents()->where('language_id', $defaultLanguage->id)->value('address'),
                    'description' => $property->contents()->where('language_id', $defaultLanguage->id)->value('description'),
                    'purpose' => $property->purpose,
                    'type' => $property->type,
                    'area' => $property->area,
                ];

                foreach ($requiredFields as $field) {
                    $value = $currentData[$field] ?? null;
                    if (is_null($value) || (is_string($value) && trim($value) === '') || $value === '') {
                        $missing[] = $field;
                    }
                }

                $property->update([
                    'missing_fields' => $missing,
                ]);
            });

            $property->refresh();
            $property->load(['contents', 'galleryImages']);

            return response()->json([
                'status' => 'success',
                'message' => 'Draft property updated successfully',
                'data' => $property,
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating draft: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update draft property',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Complete draft property
     * POST /api/properties/drafts/{id}/complete
     */
    public function completeDraft(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

            // Check property limit
            $membership = MembershipCacheService::getActiveMembership($owner->id);
            if (!$membership || !$membership->package) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active package found',
                ], 403);
            }

            $realEstateLimit = $membership->package->real_estate_limit_number;
            $currentPropertyCount = Property::where('user_id', $owner->id)
                ->where('completion_status', 'complete')
                ->count();

            if (!is_null($realEstateLimit) && $currentPropertyCount >= $realEstateLimit) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have reached your property listing limit',
                    'limit' => $realEstateLimit,
                    'used' => $currentPropertyCount,
                ], 403);
            }

            $property = Property::where('id', $id)
                ->where('user_id', $owner->id)
                ->where('completion_status', 'incomplete')
                ->first();

            if (!$property) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Draft property not found',
                ], 404);
            }

            $defaultLanguage = Language::where('user_id', $owner->id)
                ->where('is_default', 1)
                ->firstOrFail();

            // Collect all data for validation
            $propertyContent = $property->contents()->where('language_id', $defaultLanguage->id)->first();
            $completeData = [
                'title' => $request->input('title') ?? $propertyContent?->title,
                'price' => $request->input('price') ?? $property->price,
                'address' => $request->input('address') ?? $propertyContent?->address,
                'description' => $request->input('description') ?? $propertyContent?->description,
                'purpose' => $request->input('purpose') ?? $property->purpose,
                'type' => $request->input('type') ?? $property->type,
                'area' => $request->input('area') ?? $property->area,
            ];

            // Check for conflicts
            $conflictService = new \App\Services\PropertyConflictDetectionService();
            $conflicts = $conflictService->detectConflicts($property, $completeData);

            // Filter only errors (not warnings)
            $errors = array_filter($conflicts, fn($c) => $c['severity'] === 'error');

            if (!empty($errors)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot complete property due to validation errors',
                    'conflicts' => array_values($errors),
                ], 422);
            }

            DB::transaction(function () use ($property, $request, $owner, $defaultLanguage, $completeData) {
                // Update property with all data
                $propertyData = [];
                $allowedFields = ['price', 'pricePerMeter', 'purpose', 'type', 'beds', 'bath', 'area', 
                    'size', 'video_url', 'virtual_tour', 'features', 'payment_method', 
                    'water_meter_number', 'electricity_meter_number', 'deed_number', 
                    'advertising_license', 'latitude', 'longitude', 'category_id', 'project_id', 'building_id'];

                foreach ($allowedFields as $field) {
                    if ($request->has($field)) {
                        $propertyData[$field] = $request->input($field);
                    }
                }

                // Ensure required fields are set
                if (isset($completeData['price'])) $propertyData['price'] = $completeData['price'];
                if (isset($completeData['purpose'])) $propertyData['purpose'] = $completeData['purpose'];
                if (isset($completeData['type'])) $propertyData['type'] = $completeData['type'];
                if (isset($completeData['area'])) $propertyData['area'] = $completeData['area'];

                $propertyData['status'] = 1; // Active
                $propertyData['completion_status'] = 'complete';
                $propertyData['completed_at'] = now();
                $propertyData['missing_fields'] = null;
                $propertyData['validation_errors'] = null;

                $property->update($propertyData);

                // Create/update PropertyContent
                $contentData = [
                    'language_id' => $defaultLanguage->id,
                    'title' => $completeData['title'],
                    'address' => $completeData['address'],
                    'description' => $completeData['description'],
                    'meta_keyword' => null,
                    'meta_description' => Str::limit($completeData['description'], 150),
                    'category_id' => $property->category_id,
                ];

                $existingContent = PropertyContent::where('property_id', $property->id)
                    ->where('language_id', $defaultLanguage->id)
                    ->first();

                if ($existingContent) {
                    // Ensure slug is never accepted from request
                    unset($contentData['slug']);
                    // Regenerate slug if title is being updated
                    if (isset($contentData['title'])) {
                        $contentData['slug'] = PropertyContent::generateUniqueSlug($contentData['title'], $property->id);
                    }
                    $existingContent->update($contentData);
                } else {
                    PropertyContent::storePropertyContent($owner->id, $property->id, $contentData);
                }

                // Handle gallery images if provided
                if ($request->has('gallery_images') && is_array($request->gallery_images)) {
                    PropertySliderImg::where('property_id', $property->id)->delete();
                    foreach ($request->gallery_images as $imageUrl) {
                        PropertySliderImg::storeSliderImage($owner->id, $property->id, $imageUrl);
                    }
                }

                // Handle amenities if provided
                if ($request->has('amenity_ids') && is_array($request->amenity_ids)) {
                    PropertyAmenity::where('property_id', $property->id)->delete();
                    foreach ($request->amenity_ids as $amenityId) {
                        PropertyAmenity::sotreAmenity($owner->id, $property->id, $amenityId);
                    }
                }
            });

            $property->refresh();
            $property->load(['contents', 'galleryImages', 'proertyAmenities']);

            return response()->json([
                'status' => 'success',
                'message' => 'Property completed successfully',
                'data' => $property,
            ]);
        } catch (\Exception $e) {
            Log::error('Error completing draft: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to complete draft property',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Bulk complete draft properties
     * POST /api/properties/drafts/bulk-complete
     */
    public function bulkCompleteDrafts(Request $request)
    {
        try {
            $request->validate([
                'property_ids' => 'required|array',
                'property_ids.*' => 'integer|exists:user_properties,id',
            ]);

            $user = auth()->user();
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;

            // Check property limit
            $membership = MembershipCacheService::getActiveMembership($owner->id);
            if (!$membership || !$membership->package) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No active package found',
                ], 403);
            }

            $realEstateLimit = $membership->package->real_estate_limit_number;
            $currentPropertyCount = Property::where('user_id', $owner->id)
                ->where('completion_status', 'complete')
                ->count();

            $propertyIds = $request->input('property_ids');
            $completed = 0;
            $failed = 0;
            $errors = [];
            $conflictService = new \App\Services\PropertyConflictDetectionService();
            $defaultLanguage = Language::where('user_id', $owner->id)
                ->where('is_default', 1)
                ->firstOrFail();

            foreach ($propertyIds as $propertyId) {
                try {
                    DB::beginTransaction();
                    
                    // Get the property to complete
                    $property = Property::where('id', $propertyId)
                        ->where('user_id', $owner->id)
                        ->where('completion_status', 'incomplete')
                        ->first();

                    if (!$property) {
                        DB::rollBack();
                        $failed++;
                        $errors[] = [
                            'property_id' => $propertyId,
                            'error' => 'Draft property not found',
                        ];
                        continue;
                    }

                    // Check limit before completing
                    if (!is_null($realEstateLimit) && ($currentPropertyCount + $completed + 1) > $realEstateLimit) {
                        DB::rollBack();
                        $failed++;
                        $errors[] = [
                            'property_id' => $propertyId,
                            'error' => 'Property limit would be exceeded',
                        ];
                        continue;
                    }

                    // Get property data for validation
                    $propertyContent = $property->contents()->where('language_id', $defaultLanguage->id)->first();
                    $completeData = [
                        'title' => $propertyContent?->title,
                        'price' => $property->price,
                        'address' => $propertyContent?->address,
                        'description' => $propertyContent?->description,
                        'purpose' => $property->purpose,
                        'type' => $property->type,
                        'area' => $property->area,
                    ];

                    // Check for conflicts
                    $conflicts = $conflictService->detectConflicts($property, $completeData);
                    $errorConflicts = array_filter($conflicts, fn($c) => $c['severity'] === 'error');

                    if (!empty($errorConflicts)) {
                        DB::rollBack();
                        $failed++;
                        $errors[] = [
                            'property_id' => $propertyId,
                            'error' => 'Validation errors: ' . implode(', ', array_column($errorConflicts, 'message')),
                        ];
                        continue;
                    }

                    // Complete the property
                    $property->update([
                        'status' => 1,
                        'completion_status' => 'complete',
                        'completed_at' => now(),
                        'missing_fields' => null,
                        'validation_errors' => null,
                    ]);

                    // Ensure PropertyContent exists
                    if (!$propertyContent) {
                        $contentData = [
                            'language_id' => $defaultLanguage->id,
                            'title' => $completeData['title'] ?? 'Untitled',
                            'address' => $completeData['address'] ?? '',
                            'description' => $completeData['description'] ?? '',
                            'meta_keyword' => null,
                            'meta_description' => !empty($completeData['description']) ? Str::limit($completeData['description'], 150) : null,
                            'category_id' => $property->category_id,
                        ];
                        PropertyContent::storePropertyContent($owner->id, $property->id, $contentData);
                    }

                    DB::commit();
                    $completed++;
                    $currentPropertyCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $errors[] = [
                        'property_id' => $propertyId,
                        'error' => $e->getMessage(),
                    ];
                    Log::error("Error completing draft property {$propertyId}: " . $e->getMessage());
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Bulk completion processed',
                'data' => [
                    'completed_count' => $completed,
                    'failed_count' => $failed,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in bulk complete: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process bulk completion',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

}
