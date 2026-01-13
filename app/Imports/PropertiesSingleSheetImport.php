<?php

namespace App\Imports;

use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertySpecification;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User\Language;
use App\Models\User\UserDistrict;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PropertiesSingleSheetImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors;
    protected $userId;
    protected $defaultLanguageId;
    
    // Runtime caches (populated on-demand)
    protected $cityNameLookup = [];
    protected $districtNameLookup = [];
    protected $districtCityMap = [];
    
    public $importedCount = 0;
    public $updatedCount = 0;
    
    // Cache TTL in seconds (1 hour)
    private const CACHE_TTL = 3600;

    protected $limit;

    public function __construct($userId, $limit = 1000)
    {
        $this->userId = $userId;
        $this->limit = $limit;
        $this->defaultLanguageId = Language::where('user_id', $userId)
            ->where('is_default', 1)
            ->value('id');
        
        // Require default language for bulk import
        if (!$this->defaultLanguageId) {
            throw new \Exception('No default language configured for user. Please set a default language before importing properties.');
        }
        
        // No upfront loading - cities and districts are loaded lazily on demand
        // This improves constructor performance and reduces memory usage
    }

    /**
     * Limit the number of rows to read from Excel
     * This prevents reading thousands of empty rows
     */
    public function limit(): int
    {
        return $this->limit;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        
        $row      = $row->toArray();

        // Skip completely empty rows (all values are null or empty)
        $hasData = !empty(array_filter($row, function($value) {
            return !is_null($value) && $value !== '';
        }));
        
        if (!$hasData) {
            return; // Skip this empty row
        }

        // Also skip rows marked by prepareForValidation
        if (isset($row['_skip_empty_row']) && $row['_skip_empty_row'] === true) {
            return;
        }

        // Parse new relational columns
        $galleryImages = $this->parseCommaSeparated($row['gallery_images'] ?? null);
        $amenityIds = $this->parseAmenities($row, $rowIndex);
        $specifications = $this->parseSpecifications($row, $rowIndex);
        
        // Parse features (comma separated)
        $features = $this->parseCommaSeparated($row['features'] ?? null);
        
        // Parse featured (Yes/No or True/False or 1/0)
        $featuredInput = strtolower($row['featured'] ?? '');
        $featured = in_array($featuredInput, ['yes', 'true', '1']) ? 1 : 0;

        // Log parsing issues for debugging
        if (!empty($row['gallery_images']) && empty($galleryImages)) {
            Log::warning("Row {$rowIndex}: Failed to parse gallery_images", ['value' => $row['gallery_images']]);
        }
        if (!empty($row['amenity_ids']) && empty($amenityIds)) {
            Log::warning("Row {$rowIndex}: Failed to parse amenity_ids", ['value' => $row['amenity_ids']]);
        }

        [$cityId, $stateId] = $this->resolveLocationIds($row, $rowIndex);

        // Validate featured image URL format
        if (!empty($row['featured_image'])) {
            if (!$this->validateImageUrl($row['featured_image'])) {
                throw new \Exception("Row {$rowIndex}: Invalid featured_image URL format. URL must be http/https with valid image extension (jpg, jpeg, png, gif, webp).");
            }
        }

        // Validate gallery image URLs
        foreach ($galleryImages as $galleryUrl) {
            if (!$this->validateImageUrl($galleryUrl)) {
                throw new \Exception("Row {$rowIndex}: Invalid gallery image URL: {$galleryUrl}. URL must be http/https with valid image extension (jpg, jpeg, png, gif, webp).");
            }
        }

        // Check if this is an update (id column exists and has value)
        $propertyId = isset($row['id']) && !empty($row['id']) ? (int)$row['id'] : null;
        $isUpdate = false;
        $existingProperty = null;

        if ($propertyId) {
            // Check if property exists and belongs to user
            $existingProperty = Property::where('id', $propertyId)
                ->where('user_id', $this->userId)
                ->first();
            
            if ($existingProperty) {
                $isUpdate = true;
            } else {
                // ID provided but property doesn't exist or doesn't belong to user
                // Log warning and create new property instead
                Log::warning("Row {$rowIndex}: Property ID {$propertyId} not found or doesn't belong to user. Creating new property.");
                $propertyId = null;
            }
        }

        // Business rule validation (after determining if it's an update)
        $this->validateBusinessRules($row, $rowIndex, $isUpdate);

        // Wrap entire row processing in transaction
        DB::transaction(function () use ($row, $rowIndex, $galleryImages, $amenityIds, $specifications, $cityId, $stateId, $features, $featured, $isUpdate, $existingProperty, $propertyId) {
            if ($isUpdate && $existingProperty) {
                // UPDATE EXISTING PROPERTY
                $property = $existingProperty;

                // Prepare update data (only include fields that are present in the row)
                $propertyData = [];
                if (isset($row['price'])) $propertyData['price'] = $row['price'];
                if (isset($row['price_per_meter'])) $propertyData['pricePerMeter'] = $row['price_per_meter'];
                if (isset($row['purpose'])) $propertyData['purpose'] = $row['purpose'];
                if (isset($row['type'])) $propertyData['type'] = $row['type'];
                if (isset($row['beds'])) $propertyData['beds'] = $row['beds'];
                if (isset($row['bath'])) $propertyData['bath'] = $row['bath'];
                if (isset($row['area'])) $propertyData['area'] = $row['area'];
                if (isset($row['video_url'])) $propertyData['video_url'] = $row['video_url'];
                if (isset($row['virtual_tour'])) $propertyData['virtual_tour'] = $row['virtual_tour'];
                if (isset($row['status'])) $propertyData['status'] = $row['status'];
                if (isset($row['features'])) $propertyData['features'] = $features;
                if (isset($row['payment_method'])) $propertyData['payment_method'] = $this->mapPaymentMethod($row['payment_method']);
                if (isset($row['water_meter_number'])) $propertyData['water_meter_number'] = $row['water_meter_number'];
                if (isset($row['electricity_meter_number'])) $propertyData['electricity_meter_number'] = $row['electricity_meter_number'];
                if (isset($row['deed_number'])) $propertyData['deed_number'] = $row['deed_number'];
                if (isset($row['show_reservations'])) $propertyData['show_reservations'] = strtolower($row['show_reservations']) === '1' || strtolower($row['show_reservations']) === 'yes';
                $propertyData['featured'] = $featured;

                // Update featured image if provided
                if (isset($row['featured_image']) && !empty($row['featured_image'])) {
                    $propertyData['featured_image'] = $row['featured_image'];
                }

                // Update property
                $property->updateProperty($propertyData);

                // Update Property Content
                $contentData = [];
                if (isset($row['title'])) $contentData['title'] = $row['title'];
                if (isset($row['address'])) $contentData['address'] = $row['address'];
                if (isset($row['description'])) {
                    $contentData['description'] = $row['description'];
                    $contentData['meta_description'] = Str::limit($row['description'], 150);
                }
                if ($cityId) $contentData['city_id'] = $cityId;
                if ($stateId) $contentData['state_id'] = $stateId;

                // Delete existing content and create new (same as API update method)
                PropertyContent::where('property_id', $property->id)->delete();
                if (!empty($contentData) || $cityId || $stateId) {
                    $contentData['language_id'] = $this->defaultLanguageId;
                    $contentData['category_id'] = $property->category_id;
                    PropertyContent::storePropertyContent($this->userId, $property->id, $contentData);
                }

                // Replace gallery images (delete existing, add new)
                if (isset($row['gallery_images'])) {
                    PropertySliderImg::where('property_id', $property->id)->delete();
                    foreach ($galleryImages as $galleryUrl) {
                        try {
                            PropertySliderImg::storeSliderImage($this->userId, $property->id, $galleryUrl);
                        } catch (\Exception $e) {
                            Log::error("Row {$rowIndex}: Failed to store gallery image", [
                                'url' => $galleryUrl,
                                'error' => $e->getMessage()
                            ]);
                            throw new \Exception("Row {$rowIndex}: Invalid gallery image URL: {$galleryUrl}");
                        }
                    }
                }

                // Replace amenities (delete existing, add new)
                PropertyAmenity::where('property_id', $property->id)->delete();
                if (!empty($amenityIds)) {
                    foreach ($amenityIds as $amenityId) {
                        PropertyAmenity::sotreAmenity($this->userId, $property->id, $amenityId);
                    }
                }

                // Replace specifications (delete existing, add new)
                PropertySpecification::where('property_id', $property->id)->delete();
                $specIndex = 0;
                foreach ($specifications as $spec) {
                    $specData = [
                        'language_id' => $this->defaultLanguageId,
                        'key' => $specIndex++,
                        'label' => $spec['label'] ?? $spec['key'],
                        'value' => $spec['value'],
                    ];
                    PropertySpecification::storeSpecification($this->userId, $property->id, $specData);
                }

                // Update characteristics if provided
                $this->updateCharacteristics($property, $row, $rowIndex);

                $this->updatedCount++;
            } else {
                // CREATE NEW PROPERTY
                // Prepare request data for Property::storeProperty
                $propertyData = [
                    'price'           => $row['price'],
                    'pricePerMeter'   => $row['price_per_meter'] ?? null,
                    'purpose'         => $row['purpose'],
                    'type'            => $row['type'],
                    'beds'            => $row['beds'] ?? null,
                    'bath'            => $row['bath'] ?? null,
                    'area'            => $row['area'],
                    'video_url'       => $row['video_url'] ?? null,
                    'status'          => $row['status'] ?? 1,
                    'latitude'        => null,
                    'longitude'       => null,
                    'features'        => $features,
                    'region_id'       => null, // Region is automatically set to السعودية
                    'city_id'         => $cityId,
                    'category_id'     => null,
                    'show_reservations' => true,
                    'payment_method'  => $this->mapPaymentMethod($row['payment_method'] ?? null),
                ];

                // Images (Expect URLs)
                $featuredImage = $row['featured_image'] ?? null;
                $videoImage    = null;
                $floorPlans    = null;
                // $featured is now set above based on row data

                // Create Property
                $property = Property::storeProperty(
                    $this->userId,
                    $propertyData,
                    $featuredImage,
                    $floorPlans,
                    $videoImage,
                    $featured
                );

                // Create Property Content (Title, Description, Address)
                // Note: slug is auto-generated by PropertyContent::storePropertyContent
                $contentData = [
                    'language_id'      => $this->defaultLanguageId,
                    'title'            => $row['title'],
                    'address'          => $row['address'],
                    'description'      => $row['description'],
                    'meta_keyword'     => null,
                    'meta_description' => Str::limit($row['description'], 150),
                    'category_id'      => $property->category_id,
                    'city_id'          => $cityId,
                    'state_id'         => $stateId, // District ID from user_districts table
                ];

                PropertyContent::storePropertyContent($this->userId, $property->id, $contentData);

                // Process gallery images
                foreach ($galleryImages as $galleryUrl) {
                    try {
                        PropertySliderImg::storeSliderImage($this->userId, $property->id, $galleryUrl);
                    } catch (\Exception $e) {
                        Log::error("Row {$rowIndex}: Failed to store gallery image", [
                            'url' => $galleryUrl,
                            'error' => $e->getMessage()
                        ]);
                        throw new \Exception("Row {$rowIndex}: Invalid gallery image URL: {$galleryUrl}");
                    }
                }

                // Process amenities (no validation - same as API store method)
                if (!empty($amenityIds)) {
                    foreach ($amenityIds as $amenityId) {
                        PropertyAmenity::sotreAmenity($this->userId, $property->id, $amenityId);
                    }
                }

                // Process specifications (use integer keys matching API behavior)
                $specIndex = 0; // Counter for integer keys (matching API store method)
                foreach ($specifications as $spec) {
                    $specData = [
                        'language_id' => $this->defaultLanguageId,
                        'key' => $specIndex++, // Use integer counter instead of string key (matching API)
                        'label' => $spec['label'] ?? $spec['key'],
                        'value' => $spec['value'],
                    ];
                    PropertySpecification::storeSpecification($this->userId, $property->id, $specData);
                }

                // Create characteristics if provided
                $this->updateCharacteristics($property, $row, $rowIndex);
                
                $this->importedCount++;
            }
        });
    }

    /**
     * Validate image URL format
     */
    protected function validateImageUrl(string $url): bool
    {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        
        // Check scheme is http or https
        $allowedSchemes = ['http', 'https'];
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], $allowedSchemes)) {
            return false;
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $path = $parsed['path'] ?? '';
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Parse comma-separated string into array of trimmed values
     */
    protected function parseCommaSeparated(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $value)),
            fn($item) => !empty($item)
        );
    }

    /**
     * Parse comma-separated integers
     */
    protected function parseCommaSeparatedIntegers(?string $value): array
    {
        $items = $this->parseCommaSeparated($value);
        $integers = [];

        foreach ($items as $item) {
            if (is_numeric($item) && (int)$item == $item) {
                $integers[] = (int)$item;
            }
        }

        return $integers;
    }

    /**
     * Parse amenities from individual columns, additional_amenities, and amenity_ids
     * Priority: Individual columns and additional_amenities combined, fallback to amenity_ids
     */
    protected function parseAmenities(array $row, int $rowIndex): array
    {
        $amenityIds = [];

        // Map of individual amenity columns to Arabic names
        $amenityMapping = [
            'amenity_مصعد' => 'مصعد',
            'amenity_أمن' => 'أمن',
            'amenity_كاميرات_مراقبة' => 'كاميرات مراقبة',
            'amenity_تكييف_مركزي' => 'تكييف مركزي',
            'amenity_تدفئة_مركزية' => 'تدفئة مركزية',
            'amenity_صيانة' => 'صيانة',
            'amenity_بواب' => 'بواب',
            'amenity_إنترنت' => 'إنترنت',
        ];

        $amenityNames = [];

        // Process individual amenity columns (Yes/No)
        foreach ($amenityMapping as $column => $arabicName) {
            $value = $row[$column] ?? null;
            if ($this->valueProvided($value)) {
                $normalized = strtolower(trim($value));
                // Accept Yes, yes, نعم, 1, true
                if (in_array($normalized, ['yes', 'نعم', '1', 'true', 'y'])) {
                    $amenityNames[] = $arabicName;
                }
            }
        }

        // Parse additional_amenities (comma-separated names)
        $additionalAmenities = $row['additional_amenities'] ?? null;
        if ($this->valueProvided($additionalAmenities)) {
            $additionalNames = $this->parseCommaSeparated($additionalAmenities);
            $amenityNames = array_merge($amenityNames, $additionalNames);
        }

        // If we have amenity names, resolve them to IDs
        if (!empty($amenityNames)) {
            // Note: Since we don't have access to amenities table structure,
            // we'll just return the names for now
            // The actual resolution would happen in the database layer
            // For now, return empty array and let amenity_ids be used
            Log::info("Row {$rowIndex}: Found amenity names", ['names' => $amenityNames]);
            // TODO: Implement amenity name to ID resolution when amenity table structure is available
        }

        // Fallback to amenity_ids if provided (backward compatibility)
        $amenityIdsRaw = $row['amenity_ids'] ?? null;
        if ($this->valueProvided($amenityIdsRaw)) {
            $amenityIds = $this->parseCommaSeparatedIntegers($amenityIdsRaw);
        }

        return $amenityIds;
    }

    /**
     * Parse specifications from both individual columns and JSON
     * Priority: Individual columns first, then merge with JSON if provided
     */
    protected function parseSpecifications(array $row, int $rowIndex): array
    {
        $specifications = [];

        // Map of individual columns to spec keys
        $specMapping = [
            'unit_number' => ['key' => 'unit_number', 'label_en' => 'Unit Number', 'label_ar' => 'رقم الوحدة'],
            'floor_number' => ['key' => 'floor_number', 'label_en' => 'Floor Number', 'label_ar' => 'رقم الطابق'],
            'building_age' => ['key' => 'building_age', 'label_en' => 'Building Age', 'label_ar' => 'عمر المبنى'],
            'view_type' => ['key' => 'view_type', 'label_en' => 'View Type', 'label_ar' => 'نوع الإطلالة'],
            'furnished' => ['key' => 'furnished', 'label_en' => 'Furnished', 'label_ar' => 'مفروش'],
            'parking_spaces' => ['key' => 'parking_spaces', 'label_en' => 'Parking Spaces', 'label_ar' => 'مواقف السيارات'],
            'balcony' => ['key' => 'balcony', 'label_en' => 'Balcony', 'label_ar' => 'بلكونة'],
            'maid_room' => ['key' => 'maid_room', 'label_en' => 'Maid Room', 'label_ar' => 'غرفة خادمة'],
            'storage_room' => ['key' => 'storage_room', 'label_en' => 'Storage Room', 'label_ar' => 'غرفة تخزين'],
            'swimming_pool' => ['key' => 'swimming_pool', 'label_en' => 'Swimming Pool', 'label_ar' => 'مسبح'],
            'gym' => ['key' => 'gym', 'label_en' => 'Gym', 'label_ar' => 'صالة رياضية'],
            'garden_size' => ['key' => 'garden_size', 'label_en' => 'Garden Size (sqm)', 'label_ar' => 'مساحة الحديقة'],
        ];

        // Process individual columns
        foreach ($specMapping as $column => $config) {
            $value = $row[$column] ?? null;
            if ($this->valueProvided($value)) {
                $specifications[] = [
                    'key' => $config['key'],
                    'label' => $config['label_en'],
                    'value' => trim($value),
                ];
            }
        }

        // Parse JSON specifications if provided (for backward compatibility)
        $jsonValue = $row['specifications'] ?? null;
        if (!empty($jsonValue)) {
            $jsonSpecs = $this->parseSpecificationsFromJSON($jsonValue);
            // Merge JSON specs (JSON takes precedence if key conflicts)
            foreach ($jsonSpecs as $jsonSpec) {
                $existingIndex = null;
                foreach ($specifications as $index => $spec) {
                    if ($spec['key'] === $jsonSpec['key']) {
                        $existingIndex = $index;
                        break;
                    }
                }
                
                if ($existingIndex !== null) {
                    // Replace with JSON value
                    $specifications[$existingIndex] = $jsonSpec;
                } else {
                    // Add new spec from JSON
                    $specifications[] = $jsonSpec;
                }
            }
        }

        return $specifications;
    }

    /**
     * Parse specifications from JSON string or return empty array
     * Expected format: [{"key": "bedrooms", "label": "Bedrooms", "value": "3"}, ...]
     */
    protected function parseSpecificationsFromJSON(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        // Check if it's simple comma format (Name: Value, Name: Value)
        // Simple format doesn't start with '[' or '{'
        $trimmed = trim($value);
        if (!str_starts_with($trimmed, '[') && !str_starts_with($trimmed, '{')) {
            return $this->parseSimpleSpecifications($value);
        }

        // Parse as JSON
        try {
            $decoded = json_decode($value, true);
            
            if (!is_array($decoded)) {
                return [];
            }

            // Validate each specification has required fields
            $validated = [];
            foreach ($decoded as $spec) {
                if (isset($spec['key']) && isset($spec['value'])) {
                    $validated[] = [
                        'key' => $spec['key'],
                        'label' => $spec['label'] ?? $spec['key'],
                        'value' => $spec['value'],
                    ];
                }
            }

            return $validated;
        } catch (\Exception $e) {
            Log::warning('Failed to parse specifications JSON', ['value' => $value, 'error' => $e->getMessage()]);
            return [];
        }
    }

    protected function parseSimpleSpecifications(string $value): array
    {
        $specifications = [];
        
        // Split by comma
        $pairs = explode(',', $value);
        
        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) {
                continue;
            }
            
            // Split by colon
            $parts = explode(':', $pair, 2);
            if (count($parts) === 2) {
                $label = trim($parts[0]);
                $val = trim($parts[1]);
                
                $specifications[] = [
                    'key' => $label,
                    'label' => $label,
                    'value' => $val,
                ];
            }
        }
        
        return $specifications;
    }

    /**
     * Update or create property characteristics
     */
    protected function updateCharacteristics($property, array $row, int $rowIndex): void
    {
        // Only include fields that exist in UserPropertyCharacteristic model
        $characteristicFields = [
            'facade_id',
            'length',
            'width',
            'street_width_north',
            'street_width_south',
            'street_width_east',
            'street_width_west',
            'building_age',
            'rooms',
            'bathrooms',
            'floors',
            'floor_number',
            'driver_room',
            'maid_room',
            'dining_room',
            'living_room',
            'majlis',
            'storage_room',
            'basement',
            'swimming_pool',
            'kitchen',
            'balcony',
            'garden',
            'annex',
            'elevator',
            'private_parking',
            'size',
        ];

        $characteristicsData = [];
        
        foreach ($characteristicFields as $field) {
            if (isset($row[$field]) && $this->valueProvided($row[$field])) {
                $value = $row[$field];
                
                // Handle boolean fields (Yes/No, 1/0, true/false)
                if (in_array($field, ['driver_room', 'maid_room', 'dining_room', 'living_room', 'majlis', 
                    'storage_room', 'basement', 'swimming_pool', 'kitchen', 'balcony', 'garden', 
                    'annex', 'elevator', 'private_parking'])) {
                    $normalized = strtolower(trim($value));
                    $characteristicsData[$field] = in_array($normalized, ['yes', '1', 'true', 'y', 'نعم']) ? 1 : 0;
                } else {
                    $characteristicsData[$field] = $value;
                }
            }
        }

        // Map parking_spaces from specifications to private_parking in characteristics
        if (isset($row['parking_spaces']) && $this->valueProvided($row['parking_spaces'])) {
            $characteristicsData['private_parking'] = !empty($row['parking_spaces']) ? 1 : 0;
        }

        if (!empty($characteristicsData)) {
            UserPropertyCharacteristic::updateOrCreate(
                ['property_id' => $property->id],
                $characteristicsData
            );
        }
    }

    public function rules(): array
    {
        return [
            'id'          => 'nullable|integer|exists:user_properties,id',
            'title'       => 'required_without:id|string|max:255|min:3',
            'price'       => 'required_without:id|numeric|min:0',
            'price_per_meter' => 'nullable|numeric|min:0',
            'address'     => 'required_without:id|string|min:5|max:500',
            'description' => 'required_without:id|string|min:10',
            'purpose'     => 'required_without:id|in:sale,rent',
            'type'        => 'required_without:id|in:residential,commercial',
            'area'        => 'required_without:id|numeric|min:1',
            'size'        => 'nullable|numeric|min:0',
            'beds'        => 'nullable|integer|min:0|max:50',
            'bath'        => 'nullable|integer|min:0|max:50',
            'status'      => 'nullable|in:0,1,active,inactive',
            'city_name'   => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'country_name' => 'nullable|string|max:255',
            'featured_image' => 'nullable|url|max:500',
            'video_url'   => 'nullable|url|max:500',
            'virtual_tour' => 'nullable|url|max:500',
            'gallery_images' => 'nullable|string',
            'amenity_مصعد' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_أمن' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_كاميرات_مراقبة' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_تكييف_مركزي' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_تدفئة_مركزية' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_صيانة' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_بواب' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'amenity_إنترنت' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false',
            'additional_amenities' => 'nullable|string|max:1000',
            'unit_number' => 'nullable|string|max:50',
            'floor_number' => 'nullable|integer|min:0|max:200',
            'building_age' => 'nullable|integer|min:0|max:200',
            'view_type' => 'nullable|string|max:100',
            'furnished' => 'nullable|string|max:50',
            'parking_spaces' => 'nullable|integer|min:0|max:100',
            'balcony' => 'nullable|string|max:50',
            'maid_room' => 'nullable|string|max:50',
            'storage_room' => 'nullable|string|max:50',
            'swimming_pool' => 'nullable|string|max:50',
            'gym' => 'nullable|string|max:50',
            'garden_size' => 'nullable|numeric|min:0',
            'specifications' => 'nullable|string',
            'payment_method' => 'nullable|string|max:50',
            'water_meter_number' => 'nullable|string|max:100',
            'electricity_meter_number' => 'nullable|string|max:100',
            'deed_number' => 'nullable|string|max:100',
            'show_reservations' => 'nullable|in:Yes,No,yes,no,True,False,true,false,1,0',
            'reorder' => 'nullable|integer|min:0',
            'reorder_featured' => 'nullable|integer|min:0',
            'featured' => 'nullable|string|in:Yes,No,yes,no,True,False,true,false,1,0',
            'features' => 'nullable|string|max:2000',
            'faqs' => 'nullable|string|max:5000',
            'category_name' => 'nullable|string|max:255',
            'project_name' => 'nullable|string|max:255',
            'building_name' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ];
    }

    /**
     * Prepare data for validation - skip empty rows before validation runs
     */
    public function prepareForValidation($data, $index)
    {
        // Check if row is completely empty (all values are null or empty)
        $hasData = !empty(array_filter($data, function($value) {
            return !is_null($value) && $value !== '';
        }));
        
        // If row is completely empty, add a special marker to skip it
        if (!$hasData) {
            // Add required fields with dummy values to pass validation
            // This row will be filtered out in onRow() anyway
            $data['_skip_empty_row'] = true;
            // Add minimal required data to pass validation but will be skipped
            $data['title'] = '_EMPTY_ROW_SKIP_';
            $data['price'] = 0;
            $data['address'] = '_EMPTY_';
            $data['description'] = '_EMPTY_';
            $data['purpose'] = 'sale';
            $data['type'] = 'residential';
            $data['area'] = 0;
        }
        
        return $data;
    }

    protected function resolveLocationIds(array $row, int $rowIndex): array
    {
        $cityId = null;
        $stateId = null;

        $cityIdRaw = $row['city_id'] ?? null;
        $cityNameRaw = $row['city_name'] ?? null;

        if ($this->valueProvided($cityIdRaw)) {
            $cityId = $this->resolveCityId($cityIdRaw, $rowIndex);
            if (!$cityId) {
                throw new \Exception("Row {$rowIndex}: Invalid city_id: {$cityIdRaw}. Please use a valid ID or leave it blank and fill city_name.");
            }

            if ($this->valueProvided($cityNameRaw)) {
                $cityNameId = $this->resolveCityId($cityNameRaw, $rowIndex);
                if ($cityNameId && $cityNameId !== $cityId) {
                    throw new \Exception("Row {$rowIndex}: city_id ({$cityIdRaw}) does not match city_name ({$cityNameRaw}).");
                }
            }
        } elseif ($this->valueProvided($cityNameRaw)) {
            $cityId = $this->resolveCityId($cityNameRaw, $rowIndex);
            if (!$cityId) {
                throw new \Exception("Row {$rowIndex}: Unable to find a city named '{$cityNameRaw}'. Please copy a value from the reference sheet.");
            }
        }

        $stateIdRaw = $row['state_id'] ?? null;
        $districtNameRaw = $row['district_name'] ?? null;

        if ($this->valueProvided($stateIdRaw)) {
            $stateId = $this->resolveDistrictId($stateIdRaw, $cityId, $rowIndex);
            if (!$stateId) {
                throw new \Exception("Row {$rowIndex}: Invalid state_id: {$stateIdRaw}. Please use a valid ID or leave it blank and fill district_name.");
            }

            if ($this->valueProvided($districtNameRaw)) {
                $districtNameId = $this->resolveDistrictId($districtNameRaw, $cityId, $rowIndex);
                if ($districtNameId && $districtNameId !== $stateId) {
                    throw new \Exception("Row {$rowIndex}: state_id ({$stateIdRaw}) does not match district_name ({$districtNameRaw}).");
                }
            }
        } elseif ($this->valueProvided($districtNameRaw)) {
            $stateId = $this->resolveDistrictId($districtNameRaw, $cityId, $rowIndex);
            if (!$stateId) {
                $cityHint = $cityId ? ' for the selected city' : '';
                throw new \Exception("Row {$rowIndex}: Unable to find district '{$districtNameRaw}'{$cityHint}. Please copy a value from the reference sheet.");
            }
        }

        return [$cityId, $stateId];
    }

    protected function resolveCityId($value, ?int $rowIndex = null): ?int
    {
        if (!$this->valueProvided($value)) {
            return null;
        }

        // If numeric, validate ID exists via cache
        if (is_numeric($value)) {
            $id = (int) $value;
            return $this->validateCityIdExists($id) ? $id : null;
        }

        // Name-based lookup with caching
        $normalized = $this->normalizeName($value);
        
        if (!$normalized) {
            return null;
        }

        // Check runtime cache first
        if (isset($this->cityNameLookup[$normalized])) {
            $matches = $this->cityNameLookup[$normalized];
        } else {
            // Use Laravel cache with 1-hour TTL
            $cacheKey = "city_lookup:{$normalized}";
            $matches = Cache::remember($cacheKey, self::CACHE_TTL, function() use ($normalized) {
                return City::where(function($query) use ($normalized) {
                    $query->whereRaw('LOWER(name_ar) = ?', [$normalized])
                          ->orWhereRaw('LOWER(name_en) = ?', [$normalized]);
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            });
            
            // Store in runtime cache for this import session
            $this->cityNameLookup[$normalized] = $matches;
        }

        if (empty($matches)) {
            return null;
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        $prefix = $rowIndex ? "Row {$rowIndex}: " : '';
        throw new \Exception("{$prefix}Multiple cities share the name '{$value}'. Please use city_id to specify the correct city.");
    }

    protected function resolveDistrictId($value, ?int $cityId = null, ?int $rowIndex = null): ?int
    {
        if (!$this->valueProvided($value)) {
            return null;
        }

        // If numeric, validate ID exists and city mapping via cache
        if (is_numeric($value)) {
            $id = (int) $value;
            
            // Validate district exists
            if (!$this->validateDistrictIdExists($id)) {
                return null;
            }
            
            // If city is specified, validate the district belongs to that city
            if ($cityId !== null) {
                $districtCityId = $this->getDistrictCityMapping($id);
                if ($districtCityId !== $cityId) {
                    $prefix = $rowIndex ? "Row {$rowIndex}: " : '';
                    throw new \Exception("{$prefix}District ID {$id} does not belong to the specified city (city_id: {$cityId}). The district belongs to city_id: {$districtCityId}.");
                }
            }
            
            return $id;
        }

        $normalized = $this->normalizeName($value);

        if (!$normalized) {
            return null;
        }

        if ($cityId) {
            $cityKey = $this->districtCityKey($cityId, $normalized);
            
            // Check runtime cache first
            if (isset($this->districtNameLookup[$cityKey])) {
                $cityMatches = $this->districtNameLookup[$cityKey];
            } else {
                // Use Laravel cache with lazy loading for district lookup by city
                $cacheKey = "district_lookup_city:{$cityId}:{$normalized}";
                $cityMatches = Cache::remember($cacheKey, self::CACHE_TTL, function() use ($normalized, $cityId) {
                    return UserDistrict::where('city_id', $cityId)
                        ->where(function($query) use ($normalized) {
                            $query->whereRaw('LOWER(name_ar) = ?', [$normalized])
                                  ->orWhereRaw('LOWER(name_en) = ?', [$normalized]);
                        })
                        ->pluck('id')
                        ->map(fn($id) => (int) $id)
                        ->toArray();
                });
                
                // Store in runtime cache
                $this->districtNameLookup[$cityKey] = $cityMatches;
            }

            if (count($cityMatches) === 1) {
                return $cityMatches[0];
            }

            if (count($cityMatches) > 1) {
                $prefix = $rowIndex ? "Row {$rowIndex}: " : '';
                throw new \Exception("{$prefix}Multiple districts named '{$value}' exist within the selected city. Please use state_id to specify the correct district.");
            }

            // If city is specified but no district found in that city, throw error
            // Don't fall through to global search as it could assign wrong district
            $prefix = $rowIndex ? "Row {$rowIndex}: " : '';
            throw new \Exception("{$prefix}District '{$value}' not found in the specified city. Please verify the district name or use state_id.");
        }

        // Global district lookup (no city specified)
        // Check runtime cache first
        if (isset($this->districtNameLookup[$normalized])) {
            $matches = $this->districtNameLookup[$normalized];
        } else {
            // Use Laravel cache for global district lookup
            $cacheKey = "district_lookup_global:{$normalized}";
            $matches = Cache::remember($cacheKey, self::CACHE_TTL, function() use ($normalized) {
                return UserDistrict::where(function($query) use ($normalized) {
                    $query->whereRaw('LOWER(name_ar) = ?', [$normalized])
                          ->orWhereRaw('LOWER(name_en) = ?', [$normalized]);
                })
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            });
            
            // Store in runtime cache
            $this->districtNameLookup[$normalized] = $matches;
        }

        if (empty($matches)) {
            return null;
        }

        if (count($matches) === 1) {
            return $matches[0];
        }

        $prefix = $rowIndex ? "Row {$rowIndex}: " : '';
        throw new \Exception("{$prefix}Multiple districts share the name '{$value}'. Please specify city_id/state_id for clarity.");
    }

    /**
     * Validate if a city ID exists using cache
     */
    protected function validateCityIdExists(int $cityId): bool
    {
        $cacheKey = "city_exists:{$cityId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($cityId) {
            return City::where('id', $cityId)->exists();
        });
    }

    /**
     * Validate if a district ID exists using cache
     */
    protected function validateDistrictIdExists(int $districtId): bool
    {
        $cacheKey = "district_exists:{$districtId}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function() use ($districtId) {
            return UserDistrict::where('id', $districtId)->exists();
        });
    }

    /**
     * Get district-to-city mapping using cache
     */
    protected function getDistrictCityMapping(int $districtId): ?int
    {
        // Check runtime cache first
        if (isset($this->districtCityMap[$districtId])) {
            return $this->districtCityMap[$districtId];
        }

        $cacheKey = "district_city_map:{$districtId}";
        
        $cityId = Cache::remember($cacheKey, self::CACHE_TTL, function() use ($districtId) {
            return UserDistrict::where('id', $districtId)->value('city_id');
        });

        // Store in runtime cache
        if ($cityId !== null) {
            $this->districtCityMap[$districtId] = (int) $cityId;
        }

        return $cityId ? (int) $cityId : null;
    }

    protected function rememberCityName(?string $name, int $id): void
    {
        $normalized = $this->normalizeName($name);

        if (!$normalized) {
            return;
        }

        if (!isset($this->cityNameLookup[$normalized])) {
            $this->cityNameLookup[$normalized] = [];
        }

        if (!in_array($id, $this->cityNameLookup[$normalized], true)) {
            $this->cityNameLookup[$normalized][] = $id;
        }
    }

    protected function rememberDistrictName(?string $name, int $districtId, int $cityId): void
    {
        $normalized = $this->normalizeName($name);

        if (!$normalized) {
            return;
        }

        if (!isset($this->districtNameLookup[$normalized])) {
            $this->districtNameLookup[$normalized] = [];
        }
        if (!in_array($districtId, $this->districtNameLookup[$normalized], true)) {
            $this->districtNameLookup[$normalized][] = $districtId;
        }

        $cityKey = $this->districtCityKey($cityId, $normalized);
        if (!isset($this->districtNameLookup[$cityKey])) {
            $this->districtNameLookup[$cityKey] = [];
        }
        if (!in_array($districtId, $this->districtNameLookup[$cityKey], true)) {
            $this->districtNameLookup[$cityKey][] = $districtId;
        }
    }

    protected function normalizeName(?string $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Normalize Arabic characters
        $patterns = [
            // Alef variations (أ, إ, آ) -> ا
            '/[أإآ]/u' => 'ا',
            // Ta Marbuta (ة) -> Ha (ه)
            '/ة/u' => 'ه',
            // Ya (ي) -> Alef Maqsura (ى) - OR normalize both to dotless Ya
            // Common practice: normalize ى to ي or vice versa. Let's try normalizing to ي
            '/ى/u' => 'ي',
            // Remove Tashkeel (diacritics)
            '/[\x{064B}-\x{065F}]/u' => '',
            // Normalize spaces (multiple spaces to single space)
            '/\s+/u' => ' ',
        ];

        $normalized = preg_replace(array_keys($patterns), array_values($patterns), $value);
        
        return Str::lower($normalized);
    }

    protected function valueProvided($value): bool
    {
        return !(is_null($value) || $value === '');
    }

    /**
     * Validate business rules for property data
     * 
     * @param array $row Row data
     * @param int $rowIndex Row index
     * @param bool $isUpdate Whether this is an update operation
     * @return void
     * @throws \Exception If business rule validation fails
     */
    protected function validateBusinessRules(array $row, int $rowIndex, bool $isUpdate): void
    {
        // Validate price (must be positive if provided)
        if (isset($row['price']) && $this->valueProvided($row['price'])) {
            $price = is_numeric($row['price']) ? (float)$row['price'] : null;
            if ($price === null || $price < 0) {
                throw new \Exception("Row {$rowIndex}: The price must be a positive number. Provided: {$row['price']}");
            }
        }

        // Validate price_per_meter (must be positive if provided)
        if (isset($row['price_per_meter']) && $this->valueProvided($row['price_per_meter'])) {
            $pricePerMeter = is_numeric($row['price_per_meter']) ? (float)$row['price_per_meter'] : null;
            if ($pricePerMeter === null || $pricePerMeter < 0) {
                throw new \Exception("Row {$rowIndex}: The price_per_meter must be a positive number. Provided: {$row['price_per_meter']}");
            }
        }

        // Validate area (must be positive if provided)
        if (isset($row['area']) && $this->valueProvided($row['area'])) {
            $area = is_numeric($row['area']) ? (float)$row['area'] : null;
            if ($area === null || $area <= 0) {
                throw new \Exception("Row {$rowIndex}: The area must be a positive number greater than 0. Provided: {$row['area']}");
            }
        }

        // Validate size (must be positive if provided)
        if (isset($row['size']) && $this->valueProvided($row['size'])) {
            $size = is_numeric($row['size']) ? (float)$row['size'] : null;
            if ($size === null || $size < 0) {
                throw new \Exception("Row {$rowIndex}: The size must be a positive number. Provided: {$row['size']}");
            }
        }

        // Validate beds (must be non-negative integer if provided)
        if (isset($row['beds']) && $this->valueProvided($row['beds'])) {
            $beds = is_numeric($row['beds']) ? (int)$row['beds'] : null;
            if ($beds === null || $beds < 0 || $beds > 50) {
                throw new \Exception("Row {$rowIndex}: The beds must be a non-negative integer between 0 and 50. Provided: {$row['beds']}");
            }
        }

        // Validate bath (must be non-negative integer if provided)
        if (isset($row['bath']) && $this->valueProvided($row['bath'])) {
            $bath = is_numeric($row['bath']) ? (int)$row['bath'] : null;
            if ($bath === null || $bath < 0 || $bath > 50) {
                throw new \Exception("Row {$rowIndex}: The bath must be a non-negative integer between 0 and 50. Provided: {$row['bath']}");
            }
        }

        // Validate latitude if provided
        if (isset($row['latitude']) && $this->valueProvided($row['latitude'])) {
            $latitude = is_numeric($row['latitude']) ? (float)$row['latitude'] : null;
            if ($latitude === null || $latitude < -90 || $latitude > 90) {
                throw new \Exception("Row {$rowIndex}: The latitude must be a number between -90 and 90. Provided: {$row['latitude']}");
            }
        }

        // Validate longitude if provided
        if (isset($row['longitude']) && $this->valueProvided($row['longitude'])) {
            $longitude = is_numeric($row['longitude']) ? (float)$row['longitude'] : null;
            if ($longitude === null || $longitude < -180 || $longitude > 180) {
                throw new \Exception("Row {$rowIndex}: The longitude must be a number between -180 and 180. Provided: {$row['longitude']}");
            }
        }

        // Validate title length if provided
        if (isset($row['title']) && $this->valueProvided($row['title'])) {
            $titleLength = mb_strlen(trim($row['title']));
            if ($titleLength < 3) {
                throw new \Exception("Row {$rowIndex}: The title must be at least 3 characters long. Provided length: {$titleLength}");
            }
            if ($titleLength > 255) {
                throw new \Exception("Row {$rowIndex}: The title must not exceed 255 characters. Provided length: {$titleLength}");
            }
        }

        // Validate description length if provided
        if (isset($row['description']) && $this->valueProvided($row['description'])) {
            $descriptionLength = mb_strlen(trim($row['description']));
            if ($descriptionLength < 10) {
                throw new \Exception("Row {$rowIndex}: The description must be at least 10 characters long. Provided length: {$descriptionLength}");
            }
        }

        // Validate address length if provided
        if (isset($row['address']) && $this->valueProvided($row['address'])) {
            $addressLength = mb_strlen(trim($row['address']));
            if ($addressLength < 5) {
                throw new \Exception("Row {$rowIndex}: The address must be at least 5 characters long. Provided length: {$addressLength}");
            }
            if ($addressLength > 500) {
                throw new \Exception("Row {$rowIndex}: The address must not exceed 500 characters. Provided length: {$addressLength}");
            }
        }

        // Validate purpose if provided
        if (isset($row['purpose']) && $this->valueProvided($row['purpose'])) {
            $purpose = strtolower(trim($row['purpose']));
            if (!in_array($purpose, ['sale', 'rent'])) {
                throw new \Exception("Row {$rowIndex}: The purpose must be either 'sale' or 'rent'. Provided: {$row['purpose']}");
            }
        }

        // Validate type if provided
        if (isset($row['type']) && $this->valueProvided($row['type'])) {
            $type = strtolower(trim($row['type']));
            if (!in_array($type, ['residential', 'commercial'])) {
                throw new \Exception("Row {$rowIndex}: The type must be either 'residential' or 'commercial'. Provided: {$row['type']}");
            }
        }
    }

    protected function districtCityKey(int $cityId, string $normalized): string
    {
        return "{$cityId}::{$normalized}";
    }
    protected function mapPaymentMethod(?string $value): ?string
    {
        $normalized = $this->normalizeName($value);
        
        if (!$normalized) {
            return null;
        }

        $map = [
            'شهري' => 'monthly',
            'ربع سنوي' => 'quarterly',
            'نصف سنوي' => 'semi_annual',
            'سنوي' => 'annual',
        ];

        return $map[$normalized] ?? $value;
    }
}
