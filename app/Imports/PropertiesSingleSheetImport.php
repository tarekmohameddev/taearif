<?php

namespace App\Imports;

use App\Imports\Exceptions\RowValidationException;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertySpecification;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User\Language;
use App\Models\User\UserDistrict;
use App\Rules\PropertyTypeRule;
use App\Support\PropertyCompletionRequirements;
use App\Support\PropertyExcelMapping;
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
    protected $actorId;
    protected $defaultLanguageId;
    
    // Runtime caches (populated on-demand)
    protected $cityNameLookup = [];
    protected $districtNameLookup = [];
    protected $districtCityMap = [];
    
    public $importedCount = 0;
    public $updatedCount = 0;
    public $incompleteCount = 0;
    
    // Cache TTL in seconds (1 hour)
    private const CACHE_TTL = 3600;

    protected $limit;
    public $importBatchId;

    public function __construct($userId, $limit = 1000)
    {
        $this->userId = $userId;
        $this->actorId = auth()->id() ?? $userId;
        $this->limit = $limit;
        $this->defaultLanguageId = Language::where('user_id', $userId)
            ->where('is_default', 1)
            ->value('id');
        
        // Require default language for bulk import
        if (!$this->defaultLanguageId) {
            throw new \Exception('No default language configured for user. Please set a default language before importing properties.');
        }
        
        // Generate unique batch ID for this import
        $this->importBatchId = Str::uuid()->toString();
        
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

    /**
     * Arabic heading → internal (English) key for import.
     * Used so both Arabic and English template/export headers work.
     */
    private static function arabicHeaderToKey(): array
    {
        return [
            'عنوان الإعلان' => 'title',
            'السعر' => 'price',
            'العنوان' => 'address',
            'الوصف' => 'description',
            'الغرض' => 'purpose',
            'النوع' => 'type',
            'المساحة' => 'area',
            'غرف النوم' => 'beds',
            'دورات المياه' => 'bath',
            'المدينة' => 'city_name',
            'الحي' => 'district_name',
            'الصورة الرئيسية' => 'featured_image',
            'رابط الفيديو' => 'video_url',
            'الحالة' => 'status',
            'معرض الصور' => 'gallery_images',
            'مصعد' => 'amenity_مصعد',
            'أمن' => 'amenity_أمن',
            'كاميرات مراقبة' => 'amenity_كاميرات_مراقبة',
            'تكييف مركزي' => 'amenity_تكييف_مركزي',
            'تدفئة مركزية' => 'amenity_تدفئة_مركزية',
            'صيانة' => 'amenity_صيانة',
            'بواب' => 'amenity_بواب',
            'إنترنت' => 'amenity_إنترنت',
            'مرافق إضافية' => 'additional_amenities',
            'رقم الوحدة' => 'unit_number',
            'رقم الطابق' => 'floor_number',
            'عمر المبنى' => 'building_age',
            'نوع الإطلالة' => 'view_type',
            'مفروش' => 'furnished',
            'مواقف السيارات' => 'parking_spaces',
            'بلكونة' => 'balcony',
            'غرفة خادمة' => 'maid_room',
            'غرفة تخزين' => 'storage_room',
            'مسبح' => 'swimming_pool',
            'صالة رياضية' => 'gym',
            'مساحة الحديقة' => 'garden_size',
            'المواصفات' => 'specifications',
            'طريقة الدفع' => 'payment_method',
            'مميز' => 'featured',
            'مميزات' => 'features',
            'المعرّف' => 'id',
            // Raw PropertiesExport-only headers (mapped so reject logic can detect them)
            'معرّف المستخدم' => 'user_id',
            'اسم المستخدم' => 'user_name',
            'أنشئ بواسطة' => 'created_by',
            'اسم المنشئ' => 'creator_name',
            'تاريخ الإنشاء' => 'created_at',
            'تاريخ التحديث' => 'updated_at',
        ];
    }

    /**
     * Arabic + Maatwebsite-slugified Arabic headers → English keys.
     * With heading_row.formatter = slug, Maatwebsite uses Str::slug($header, '_'),
     * so "عنوان الإعلان" arrives as "aanoan_alaaalan".
     *
     * @return array<string, string>
     */
    private static function headerKeyMap(): array
    {
        static $map = null;
        if ($map !== null) {
            return $map;
        }

        $map = [];
        foreach (self::arabicHeaderToKey() as $arabic => $english) {
            $map[$arabic] = $english;

            $arabicSlug = Str::slug($arabic, '_');
            if ($arabicSlug !== '') {
                $map[$arabicSlug] = $english;
            }

            // English keys that contain Arabic (e.g. amenity_مصعد) are also slugified
            $englishSlug = Str::slug($english, '_');
            if ($englishSlug !== '' && $englishSlug !== $english) {
                $map[$englishSlug] = $english;
            }
        }

        return $map;
    }

    /**
     * Detect PropertiesExport (raw) rows that must not be imported.
     *
     * Option A: only reject when export-only metadata columns carry VALUES.
     * Template / ImportReady / new clean exports (template columns only, or
     * empty metadata headers) must pass. Legacy raw exports with user_id,
     * created_at, etc. filled in are rejected with a clear message.
     *
     * المعرّف / id alone is ignored (create-only) and does not mark raw export.
     *
     * @param  array<string, mixed>  $row
     */
    private function isRawExportRow(array $row): bool
    {
        $exportOnlyColumns = ['slug', 'user_id', 'user_name', 'created_by', 'creator_name', 'created_at', 'updated_at'];
        foreach ($exportOnlyColumns as $col) {
            if (array_key_exists($col, $row) && $this->valueProvided($row[$col])) {
                return true;
            }
        }

        // Old comma-separated amenities column without individual amenity_* flags
        if ($this->valueProvided($row['amenities'] ?? null) && !isset($row['amenity_مصعد'])) {
            return true;
        }

        return false;
    }
    /**
     * Drop the trailing " *" the blank template puts on required headings.
     *
     * With heading_row.formatter = slug this is already handled (Str::slug drops
     * the asterisk), so this is a safety net for the exact-Arabic lookup path and
     * for any future switch to formatter = none.
     */
    private static function stripRequiredMarker(string $header): string
    {
        return trim(preg_replace('/\s*\*\s*$/u', '', trim($header)));
    }

    /**
     * Normalize row keys: map Arabic / slugified Arabic headers to English, keep existing English keys.
     */
    private function normalizeRowKeys(array $row): array
    {
        $map = self::headerKeyMap();
        $out = [];
        foreach ($row as $header => $value) {
            $headerStr = is_string($header) ? $header : (string) $header;
            $key = $map[$headerStr] ?? $map[self::stripRequiredMarker($headerStr)] ?? $headerStr;
            if ($this->valueProvided($value)) {
                $out[$key] = $value;
            } elseif (!array_key_exists($key, $out)) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    /**
     * Excel often stores 0/1 and numeric unit numbers as integers; Laravel string rules reject them.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function castStringFieldsForValidation(array $data): array
    {
        $stringFields = [
            'amenity_مصعد',
            'amenity_أمن',
            'amenity_كاميرات_مراقبة',
            'amenity_تكييف_مركزي',
            'amenity_تدفئة_مركزية',
            'amenity_صيانة',
            'amenity_بواب',
            'amenity_إنترنت',
            'unit_number',
            'view_type',
            'furnished',
            'balcony',
            'maid_room',
            'storage_room',
            'swimming_pool',
            'gym',
            'featured',
        ];

        foreach ($stringFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $data[$field] = (string) $data[$field];
            }
        }

        return $data;
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        
        $row = $row->toArray();
        $row = $this->normalizeRowKeys($row);

        // Excel → DB: purpose, type (accepts Arabic and English)
        if (array_key_exists('purpose', $row)) {
            $row['purpose'] = PropertyExcelMapping::purposeToDb($row['purpose']);
        }
        if (array_key_exists('type', $row)) {
            $row['type'] = PropertyExcelMapping::typeToDb($row['type']);
        }

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

        // SAFETY CHECK: Reject raw exports only when metadata columns have values
        // (see isRawExportRow). Clean template / ImportReady files must pass.
        // Prefer validation (_raw_export) when WithValidation runs first; this is
        // a belt-and-suspenders path if onRow sees the row anyway.
        if ($this->isRawExportRow($row)) {
            throw new \Exception("Row {$rowIndex}: Invalid file format. This appears to be a raw export file. Please use the official Import Template or the 'Export for Import' feature to get a safe, re-importable file.");
        }

        // Bulk import is create-only: ignore المعرّف / id (do not update existing rows).
        unset($row['id']);

        // Parse new relational columns
        $galleryImages = $this->parseCommaSeparated($row['gallery_images'] ?? null);
        $amenityIds = $this->parseAmenities($row, $rowIndex);
        $specifications = $this->parseSpecifications($row, $rowIndex);
        
        // Ensure all parsed values are arrays
        if (!is_array($galleryImages)) {
            $galleryImages = [];
        }
        if (!is_array($amenityIds)) {
            $amenityIds = [];
        }
        if (!is_array($specifications)) {
            $specifications = [];
        }
        
        // Parse features (comma separated)
        $features = $this->parseCommaSeparated($row['features'] ?? null);
        if (!is_array($features)) {
            $features = [];
        }
        
        // Parse featured (نعم/Yes/1/true/y or لا/No/0/false)
        $featured = PropertyExcelMapping::booleanToDb($row['featured'] ?? null) ? 1 : 0;

        // Log parsing issues for debugging
        if (!empty($row['gallery_images']) && empty($galleryImages)) {
            Log::warning("Row {$rowIndex}: Failed to parse gallery_images", ['value' => $row['gallery_images']]);
        }
        if (!empty($row['amenity_ids']) && empty($amenityIds)) {
            Log::warning("Row {$rowIndex}: Failed to parse amenity_ids", ['value' => $row['amenity_ids']]);
        }

        [$cityId, $stateId] = $this->resolveLocationIds($row, $rowIndex);

        // Soft validation_errors (Decision B): missing requireds + format issues
        // create an incomplete draft; only bad city / raw-export hard-fail the row.
        $missingFields = [];
        $validationErrors = [];

        // Location: empty city is OK for complete; unresolvable district is soft.
        if ($this->valueProvided($row['district_name'] ?? null) && !$stateId && $cityId) {
            $validationErrors[] = "District '{$row['district_name']}' not found in the specified city. Please verify the district name or use state_id.";
        }

        // Image URL validation: soft-fail. Invalid featured_image / gallery URLs
        // go into validation_errors → incomplete draft (not a hard row failure).
        if ($this->valueProvided($row['featured_image'] ?? null)) {
            if (!$this->validateImageUrl($row['featured_image'])) {
                $validationErrors[] = "featured_image URL format is invalid: must be http/https with jpg, jpeg, png, gif, or webp extension";
            }
        }

        foreach ($galleryImages as $galleryUrl) {
            if (!$this->validateImageUrl($galleryUrl)) {
                $validationErrors[] = "gallery_images contains invalid URL: {$galleryUrl} (must be http/https with jpg, jpeg, png, gif, or webp extension)";
                break;
            }
        }

        // Soft-fail business rules for creates
        $businessViolations = $this->validateBusinessRules($row, $rowIndex, false);
        if (!empty($businessViolations)) {
            $validationErrors = array_merge(
                $validationErrors,
                array_column($businessViolations, 'message')
            );
        }
        
        // Completeness is the shared five-field definition: title, address,
        // description, featured_image, property_type. Reported as canonical
        // English keys (`property_type`, never the Excel alias `type`).
        $missingFields = PropertyCompletionRequirements::missingFrom($row);

        // Format checks run only on values that were actually supplied. A blank
        // required field is already reported once in $missingFields and must not
        // error twice; a blank price / purpose / area is simply absent, not invalid.
        if ($this->valueProvided($row['title'] ?? null)) {
            $length = mb_strlen((string) $row['title']);
            if ($length < 3 || $length > 255) {
                $validationErrors[] = "title must be between 3 and 255 characters";
            }
        }

        if ($this->valueProvided($row['address'] ?? null)) {
            $length = mb_strlen((string) $row['address']);
            if ($length < 5 || $length > 500) {
                $validationErrors[] = "address must be between 5 and 500 characters";
            }
        }

        if ($this->valueProvided($row['description'] ?? null)
            && mb_strlen((string) $row['description']) < 10) {
            $validationErrors[] = "description must be at least 10 characters";
        }

        if ($this->valueProvided($row['price'] ?? null)) {
            if (!is_numeric($row['price'])) {
                $validationErrors[] = "price must be a number";
            } elseif ($row['price'] < 0) {
                $validationErrors[] = "price must be at least 0";
            }
        }

        if ($this->valueProvided($row['area'] ?? null)) {
            if (!is_numeric($row['area'])) {
                $validationErrors[] = "area must be a number";
            } elseif ($row['area'] < 1) {
                $validationErrors[] = "area must be at least 1";
            }
        }

        if ($this->valueProvided($row['purpose'] ?? null)
            && !in_array($row['purpose'], ['sale', 'rent'], true)) {
            $validationErrors[] = "purpose must be one of: sale, rent";
        }

        $rowType = $row['type'] ?? null;
        if ($this->valueProvided($rowType)) {
            $normalizedType = is_string($rowType) ? PropertyTypeRule::normalize($rowType) : null;
            if ($normalizedType === null || !in_array($normalizedType, PropertyTypeRule::allowed(), true)) {
                $validationErrors[] = 'property_type must be one of: '
                    . implode(', ', PropertyTypeRule::allowed());
            }
        }

        // Wrap entire row processing in transaction (create-only)
        DB::transaction(function () use ($row, $rowIndex, $galleryImages, $amenityIds, $specifications, $cityId, $stateId, $features, $featured, $missingFields, $validationErrors) {
            // CREATE NEW PROPERTY
            
            // Check if this is an incomplete property (missing required fields)
            if (!empty($missingFields) || !empty($validationErrors)) {
                // Create incomplete property with partial data
                $propertyData = [
                    'price'           => $row['price'] ?? null,
                    'pricePerMeter'   => $row['price_per_meter'] ?? null,
                    'purpose'         => $row['purpose'] ?? null,
                    'property_type'   => $row['type'] ?? null,
                    'beds'            => $row['beds'] ?? null,
                    'bath'            => $row['bath'] ?? null,
                    'area'            => $row['area'] ?? null,
                    'video_url'       => $row['video_url'] ?? null,
                    'status'          => 0, // Hidden
                    'latitude'        => null,
                    'longitude'       => null,
                    'features'        => $features,
                    'region_id'       => null,
                    'city_id'         => $cityId,
                    'category_id'     => null,
                    'show_reservations' => true,
                    'payment_method'  => $this->mapPaymentMethod($row['payment_method'] ?? null),
                    'completion_status' => 'incomplete',
                    'missing_fields'  => $missingFields,
                    'validation_errors' => $validationErrors,
                    'import_batch_id' => $this->importBatchId,
                ];

                // Images (Expect URLs)
                $featuredImage = $row['featured_image'] ?? null;
                $videoImage    = null;
                $floorPlans    = null;

                // Create incomplete Property
                $property = Property::storeProperty(
                    $this->userId,
                    $propertyData,
                    $featuredImage,
                    $floorPlans,
                    $videoImage,
                    $featured,
                    $this->actorId
                );

                // Create PropertyContent when EITHER title or address is present.
                // Requiring both left a title-only draft with no content row at
                // all, so it showed up nameless in the drafts UI.
                if ($this->valueProvided($row['title'] ?? null) || $this->valueProvided($row['address'] ?? null)) {
                    $contentData = [
                        'language_id'      => $this->defaultLanguageId,
                        'title'            => $row['title'] ?? '',
                        'address'          => $row['address'] ?? '',
                        'description'      => $row['description'] ?? '',
                        'meta_keyword'     => null,
                        'meta_description' => !empty($row['description']) ? Str::limit($row['description'], 150) : null,
                        'category_id'      => $property->category_id,
                        'city_id'          => $cityId,
                        'state_id'         => $stateId,
                    ];

                    PropertyContent::storePropertyContent($this->userId, $property->id, $contentData);
                }

                // Process gallery images if provided
                if (is_array($galleryImages) && !empty($galleryImages)) {
                    $this->bulkInsertGalleryImages($property->id, $galleryImages);
                }

                // Process amenities if provided
                if (!empty($amenityIds) && is_array($amenityIds)) {
                    $this->bulkInsertAmenities($property->id, $amenityIds);
                }

                // Process specifications if provided
                if (is_array($specifications) && !empty($specifications)) {
                    $this->bulkInsertSpecifications($property->id, $specifications);
                }

                // Create characteristics if provided
                $this->updateCharacteristics($property, $row, $rowIndex);
                
                $this->incompleteCount++;
            } else {
                // All required fields present - create complete property.
                // price / purpose / area are optional now, so every read must be
                // null-safe: Property::storeProperty() indexes price and area
                // directly and would fatal on an absent key.
                $propertyData = [
                    'price'           => $row['price'] ?? null,
                    'pricePerMeter'   => $row['price_per_meter'] ?? null,
                    'purpose'         => $row['purpose'] ?? null,
                    'property_type'   => $row['type'] ?? null,
                    'beds'            => $row['beds'] ?? null,
                    'bath'            => $row['bath'] ?? null,
                    'area'            => $row['area'] ?? null,
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
                    'completion_status' => 'complete',
                    // Was missing: complete rows were never tagged with their batch,
                    // so the bulk-import report could not see them.
                    'import_batch_id' => $this->importBatchId,
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
                    $featured,
                    $this->actorId
                );

                // Create Property Content (Title, Description, Address)
                // Note: slug is auto-generated by PropertyContent::storePropertyContent
                $contentData = [
                    'language_id'      => $this->defaultLanguageId,
                    'title'            => $row['title'],
                    'address'          => $row['address'],
                    'description'      => $row['description'],
                    'meta_keyword'     => null,
                    'meta_description' => Str::limit((string) $row['description'], 150),
                    'category_id'      => $property->category_id,
                    'city_id'          => $cityId,
                    'state_id'         => $stateId, // District ID from user_districts table
                ];

                PropertyContent::storePropertyContent($this->userId, $property->id, $contentData);

                // Process gallery images
                if (is_array($galleryImages) && !empty($galleryImages)) {
                    $this->bulkInsertGalleryImages($property->id, $galleryImages);
                }

                // Process amenities (no validation - same as API store method)
                if (!empty($amenityIds) && is_array($amenityIds)) {
                    $this->bulkInsertAmenities($property->id, $amenityIds);
                }

                // Process specifications (use integer keys matching API behavior)
                if (is_array($specifications) && !empty($specifications)) {
                    $this->bulkInsertSpecifications($property->id, $specifications);
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
    protected function parseCommaSeparated($value): array
    {
        if (empty($value)) {
            return [];
        }
        
        // If already an array, return as-is
        if (is_array($value)) {
            return array_filter($value, fn($item) => !empty($item));
        }
        
        // Convert to string if not already
        $stringValue = (string) $value;
        if (empty($stringValue)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $stringValue)),
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
            if (is_array($additionalNames)) {
                $amenityNames = array_merge($amenityNames, $additionalNames);
            }
        }

        // If we have amenity names, resolve them to IDs
        if (!empty($amenityNames) && is_array($amenityNames)) {
            // Resolve names to IDs from database
            $amenityIdsFromDb = Amenity::where('user_id', $this->userId)
                ->whereIn('name', $amenityNames)
                ->pluck('id')
                ->toArray();
            
            if (!empty($amenityIdsFromDb)) {
                // Log success for debugging
                Log::info("Row {$rowIndex}: Resolved amenity names", ['names' => $amenityNames, 'ids' => $amenityIdsFromDb]);
                
                // Merge with existing IDs (avoiding duplicates)
                foreach ($amenityIdsFromDb as $id) {
                    if (!in_array($id, $amenityIds)) {
                        $amenityIds[] = (int)$id;
                    }
                }
            } else {
                Log::warning("Row {$rowIndex}: Found amenity names but could not resolve to any IDs", ['names' => $amenityNames]);
            }
        }

        // Fallback to amenity_ids if provided (backward compatibility)
        $amenityIdsRaw = $row['amenity_ids'] ?? null;
        if ($this->valueProvided($amenityIdsRaw)) {
            $amenityIds = $this->parseCommaSeparatedIntegers($amenityIdsRaw);
        }

        // Ensure we always return an array
        return is_array($amenityIds) ? $amenityIds : [];
    }

    /**
     * Parse specifications from both individual columns and JSON
     * Priority: Individual columns first, then merge with JSON if provided
     */
    protected function parseSpecifications(array $row, int $rowIndex): array
    {
        $specifications = [];
        
        // Ensure we always return an array
        if (!is_array($row)) {
            return [];
        }

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
            // Ensure jsonSpecs is an array
            if (is_array($jsonSpecs)) {
                // Merge JSON specs (JSON takes precedence if key conflicts)
                foreach ($jsonSpecs as $jsonSpec) {
                    if (!is_array($jsonSpec)) {
                        continue; // Skip invalid entries
                    }
                    $existingIndex = null;
                    foreach ($specifications as $index => $spec) {
                        if (is_array($spec) && isset($spec['key']) && isset($jsonSpec['key']) && $spec['key'] === $jsonSpec['key']) {
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
        }

        // Ensure we always return an array
        return is_array($specifications) ? $specifications : [];
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
        // Note: Fields are intentionally NOT required here to allow incomplete properties
        // Custom validation in onRow() determines completeness and creates incomplete properties if needed
        return [
            'title'       => 'nullable|string|max:255',
            'price'       => 'nullable|numeric|min:0',
            'price_per_meter' => 'nullable|numeric|min:0',
            'address'     => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'purpose'     => 'nullable|in:sale,rent',
            'type'        => 'nullable|in:residential,commercial,agricultural,industrial',
            'area'        => 'nullable|numeric|min:0',
            'size'        => 'nullable|numeric|min:0',
            'beds'        => 'nullable|integer|min:0|max:50',
            'bath'        => 'nullable|integer|min:0|max:50',
            'status'      => 'nullable|in:0,1,active,inactive',
            'city_name'   => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'country_name' => 'nullable|string|max:255',
            'featured_image' => 'nullable|string|max:500',
            'video_url'   => 'nullable|url|max:500',
            'virtual_tour' => 'nullable|url|max:500',
            'gallery_images' => 'nullable|string',
            'amenity_مصعد' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_أمن' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_كاميرات_مراقبة' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_تكييف_مركزي' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_تدفئة_مركزية' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_صيانة' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_بواب' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
            'amenity_إنترنت' => 'nullable|string|max:10|in:Yes,No,yes,no,1,0,true,false,نعم,لا',
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
            'featured' => 'nullable|string|in:Yes,No,yes,no,True,False,true,false,1,0,نعم,لا',
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
     * Prepare data for validation - remap slug/Arabic headers and map purpose/type
     * so WithValidation rules see English keys and DB enum values.
     */
        public function customValidationMessages(): array
    {
        return [
            '_raw_export.prohibited' => 'Invalid file format. It appears you are trying to import a raw Export file which is not supported. Please copy your data into the official Import Template.',
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $rowIndex => $row) {
                if (!is_array($row)) {
                    continue;
                }
                // Marker set in prepareForValidation for raw PropertiesExport rows
                if (($row['_raw_export'] ?? null) === '1') {
                    $validator->errors()->add(
                        $rowIndex . '._raw_export',
                        "Row {$rowIndex}: Invalid file format. It appears you are trying to import a raw Export file which is not supported. Please copy your data into the official Import Template."
                    );
                }
            }
        });
    }
    public function prepareForValidation($data, $index)
    {
        $data = $this->normalizeRowKeys(is_array($data) ? $data : (array) $data);

        if (array_key_exists('purpose', $data)) {
            $mapped = PropertyExcelMapping::purposeToDb($data['purpose']);
            if ($mapped !== null) {
                $data['purpose'] = $mapped;
            }
        }
        if (array_key_exists('type', $data)) {
            $mapped = PropertyExcelMapping::typeToDb($data['type']);
            if ($mapped !== null) {
                $data['type'] = $mapped;
            }
        }

        $data = $this->castStringFieldsForValidation($data);

        // Raw-export files map id + export-only columns (user_id, created_at, …).
        // WithValidation runs before onRow(), and OnEachRow does not swallow exceptions
        // via SkipsOnError — so reject through validation (see withValidator) instead of throwing.
        if ($this->isRawExportRow($data)) {
            unset($data['id']);
            $data['_raw_export'] = '1';
        }

        // Bulk import is create-only: ignore المعرّف / id even on import-safe files.
        unset($data['id']);

        // Check if row is completely empty (all values are null or empty)
        $hasData = !empty(array_filter($data, function ($value) {
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
                throw new RowValidationException(
                    "Row {$rowIndex}: Invalid city_id: {$cityIdRaw}. Please use a valid ID or leave it blank and fill city_name.",
                    field: 'city_id',
                    row: $rowIndex
                );
            }

            if ($this->valueProvided($cityNameRaw)) {
                $cityNameId = $this->resolveCityId($cityNameRaw, $rowIndex);
                if ($cityNameId && $cityNameId !== $cityId) {
                    throw new RowValidationException(
                        "Row {$rowIndex}: city_id ({$cityIdRaw}) does not match city_name ({$cityNameRaw}).",
                        field: 'city_id',
                        row: $rowIndex
                    );
                }
            }
        } elseif ($this->valueProvided($cityNameRaw)) {
            $cityId = $this->resolveCityId($cityNameRaw, $rowIndex);
            if (!$cityId) {
                throw new RowValidationException(
                    "Row {$rowIndex}: Unable to find a city named '{$cityNameRaw}'. Please copy a value from the reference sheet.",
                    field: 'city_id',
                    row: $rowIndex
                );
            }
        }

        $stateIdRaw = $row['state_id'] ?? null;
        $districtNameRaw = $row['district_name'] ?? null;

        if ($this->valueProvided($stateIdRaw)) {
            $stateId = $this->resolveDistrictId($stateIdRaw, $cityId, $rowIndex);
            if (!$stateId) {
                throw new RowValidationException(
                    "Row {$rowIndex}: Invalid state_id: {$stateIdRaw}. Please use a valid ID or leave it blank and fill district_name.",
                    field: 'state_id',
                    row: $rowIndex
                );
            }

            if ($this->valueProvided($districtNameRaw)) {
                $districtNameId = $this->resolveDistrictId($districtNameRaw, $cityId, $rowIndex);
                if ($districtNameId && $districtNameId !== $stateId) {
                    throw new RowValidationException(
                        "Row {$rowIndex}: state_id ({$stateIdRaw}) does not match district_name ({$districtNameRaw}).",
                        field: 'state_id',
                        row: $rowIndex
                    );
                }
            }
        } elseif ($this->valueProvided($districtNameRaw)) {
            try {
                $stateId = $this->resolveDistrictId($districtNameRaw, $cityId, $rowIndex);
                // If district not found, allow null - will be tracked as validation error for incomplete properties
            } catch (\Exception $e) {
                // If district resolution fails (e.g., multiple matches), allow null for incomplete properties
                // The error will be tracked in validation_errors
                $stateId = null;
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
        throw new RowValidationException(
            "{$prefix}Multiple cities share the name '{$value}'. Please use city_id to specify the correct city.",
            field: 'city_id',
            row: $rowIndex
        );
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
                    throw new RowValidationException(
                        "{$prefix}District ID {$id} does not belong to the specified city (city_id: {$cityId}). The district belongs to city_id: {$districtCityId}.",
                        field: 'state_id',
                        row: $rowIndex
                    );
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
                throw new RowValidationException(
                    "{$prefix}Multiple districts named '{$value}' exist within the selected city. Please use state_id to specify the correct district.",
                    field: 'state_id',
                    row: $rowIndex
                );
            }

            // If city is specified but no district found in that city, return null
            // Don't fall through to global search as it could assign wrong district
            // This allows incomplete property creation with location validation errors
            return null;
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
        throw new RowValidationException(
            "{$prefix}Multiple districts share the name '{$value}'. Please specify city_id/state_id for clarity.",
            field: 'state_id',
            row: $rowIndex
        );
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
     * Validate business rules for property data.
     * Soft-fail fields return violation entries; hard-fail fields throw RowValidationException.
     *
     * @param array $row Row data
     * @param int $rowIndex Row index
     * @param bool $isUpdate Whether this is an update operation
     * @return array<int, array{field: string, message: string}>
     * @throws RowValidationException
     */
    protected function validateBusinessRules(array $row, int $rowIndex, bool $isUpdate): array
    {
        $violations = [];

        // Validate price (must be positive if provided)
        if (isset($row['price']) && $this->valueProvided($row['price'])) {
            $price = is_numeric($row['price']) ? (float)$row['price'] : null;
            if ($price === null || $price < 0) {
                $violations[] = [
                    'field' => 'price',
                    'message' => "The price must be a positive number. Provided: {$row['price']}",
                ];
            }
        }

        // Validate price_per_meter (must be positive if provided) — hard-fail
        if (isset($row['price_per_meter']) && $this->valueProvided($row['price_per_meter'])) {
            $pricePerMeter = is_numeric($row['price_per_meter']) ? (float)$row['price_per_meter'] : null;
            if ($pricePerMeter === null || $pricePerMeter < 0) {
                throw new RowValidationException(
                    "Row {$rowIndex}: The price_per_meter must be a positive number. Provided: {$row['price_per_meter']}",
                    field: 'price_per_meter',
                    row: $rowIndex
                );
            }
        }

        // Validate area (must be positive if provided)
        if (isset($row['area']) && $this->valueProvided($row['area'])) {
            $area = is_numeric($row['area']) ? (float)$row['area'] : null;
            if ($area === null || $area <= 0) {
                $violations[] = [
                    'field' => 'area',
                    'message' => "The area must be a positive number greater than 0. Provided: {$row['area']}",
                ];
            }
        }

        // Validate size (must be positive if provided) — hard-fail
        if (isset($row['size']) && $this->valueProvided($row['size'])) {
            $size = is_numeric($row['size']) ? (float)$row['size'] : null;
            if ($size === null || $size < 0) {
                throw new RowValidationException(
                    "Row {$rowIndex}: The size must be a positive number. Provided: {$row['size']}",
                    field: 'size',
                    row: $rowIndex
                );
            }
        }

        // Validate beds (must be non-negative integer if provided)
        if (isset($row['beds']) && $this->valueProvided($row['beds'])) {
            $beds = is_numeric($row['beds']) ? (int)$row['beds'] : null;
            if ($beds === null || $beds < 0 || $beds > 50) {
                $violations[] = [
                    'field' => 'beds',
                    'message' => "The beds must be a non-negative integer between 0 and 50. Provided: {$row['beds']}",
                ];
            }
        }

        // Validate bath (must be non-negative integer if provided)
        if (isset($row['bath']) && $this->valueProvided($row['bath'])) {
            $bath = is_numeric($row['bath']) ? (int)$row['bath'] : null;
            if ($bath === null || $bath < 0 || $bath > 50) {
                $violations[] = [
                    'field' => 'bath',
                    'message' => "The bath must be a non-negative integer between 0 and 50. Provided: {$row['bath']}",
                ];
            }
        }

        // Validate latitude if provided — hard-fail
        if (isset($row['latitude']) && $this->valueProvided($row['latitude'])) {
            $latitude = is_numeric($row['latitude']) ? (float)$row['latitude'] : null;
            if ($latitude === null || $latitude < -90 || $latitude > 90) {
                throw new RowValidationException(
                    "Row {$rowIndex}: The latitude must be a number between -90 and 90. Provided: {$row['latitude']}",
                    field: 'latitude',
                    row: $rowIndex
                );
            }
        }

        // Validate longitude if provided — hard-fail
        if (isset($row['longitude']) && $this->valueProvided($row['longitude'])) {
            $longitude = is_numeric($row['longitude']) ? (float)$row['longitude'] : null;
            if ($longitude === null || $longitude < -180 || $longitude > 180) {
                throw new RowValidationException(
                    "Row {$rowIndex}: The longitude must be a number between -180 and 180. Provided: {$row['longitude']}",
                    field: 'longitude',
                    row: $rowIndex
                );
            }
        }

        // Validate title length if provided
        if (isset($row['title']) && $this->valueProvided($row['title'])) {
            $titleLength = mb_strlen(trim($row['title']));
            if ($titleLength < 3) {
                $violations[] = [
                    'field' => 'title',
                    'message' => "The title must be at least 3 characters long. Provided length: {$titleLength}",
                ];
            } elseif ($titleLength > 255) {
                $violations[] = [
                    'field' => 'title',
                    'message' => "The title must not exceed 255 characters. Provided length: {$titleLength}",
                ];
            }
        }

        // Validate description length if provided
        if (isset($row['description']) && $this->valueProvided($row['description'])) {
            $descriptionLength = mb_strlen(trim($row['description']));
            if ($descriptionLength < 10) {
                $violations[] = [
                    'field' => 'description',
                    'message' => "The description must be at least 10 characters long. Provided length: {$descriptionLength}",
                ];
            }
        }

        // Validate address length if provided
        if (isset($row['address']) && $this->valueProvided($row['address'])) {
            $addressLength = mb_strlen(trim($row['address']));
            if ($addressLength < 5) {
                $violations[] = [
                    'field' => 'address',
                    'message' => "The address must be at least 5 characters long. Provided length: {$addressLength}",
                ];
            } elseif ($addressLength > 500) {
                $violations[] = [
                    'field' => 'address',
                    'message' => "The address must not exceed 500 characters. Provided length: {$addressLength}",
                ];
            }
        }

        // Validate purpose if provided
        if (isset($row['purpose']) && $this->valueProvided($row['purpose'])) {
            $purpose = strtolower(trim($row['purpose']));
            if (!in_array($purpose, ['sale', 'rent'])) {
                $violations[] = [
                    'field' => 'purpose',
                    'message' => "The purpose must be either 'sale' or 'rent'. Provided: {$row['purpose']}",
                ];
            }
        }

        // Validate type if provided
        if (isset($row['type']) && $this->valueProvided($row['type'])) {
            $type = strtolower(trim($row['type']));
            if (!in_array($type, ['residential', 'commercial'])) {
                $violations[] = [
                    'field' => 'type',
                    'message' => "The type must be either 'residential' or 'commercial'. Provided: {$row['type']}",
                ];
            }
        }

        return $violations;
    }

    /**
     * Bulk-insert gallery slider images for a property (N+1 fix).
     */
    protected function bulkInsertGalleryImages(int $propertyId, array $galleryImages): void
    {
        $now = now();
        $rows = [];
        foreach ($galleryImages as $galleryUrl) {
            if ($galleryUrl === null || $galleryUrl === '') {
                continue;
            }
            $rows[] = [
                'user_id' => $this->userId,
                'property_id' => $propertyId,
                'image' => $galleryUrl,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            PropertySliderImg::insert($rows);
        }
    }

    /**
     * Bulk-insert amenity pivots for a property (N+1 fix).
     * Inlined because PropertyAmenity::sotreAmenity only supports single create().
     */
    protected function bulkInsertAmenities(int $propertyId, array $amenityIds): void
    {
        $now = now();
        $rows = [];
        foreach ($amenityIds as $amenityId) {
            if ($amenityId === null || $amenityId === '') {
                continue;
            }
            $rows[] = [
                'user_id' => $this->userId,
                'property_id' => $propertyId,
                'amenity_id' => $amenityId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            PropertyAmenity::insert($rows);
        }
    }

    /**
     * Bulk-insert specifications for a property (N+1 fix).
     */
    protected function bulkInsertSpecifications(int $propertyId, array $specifications): void
    {
        $now = now();
        $rows = [];
        $specIndex = 0;
        foreach ($specifications as $spec) {
            if (!is_array($spec)) {
                continue;
            }
            $rows[] = [
                'user_id' => $this->userId,
                'property_id' => $propertyId,
                'language_id' => $this->defaultLanguageId,
                'key' => $specIndex++,
                'label' => $spec['label'] ?? $spec['key'] ?? '',
                'value' => $spec['value'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($rows)) {
            PropertySpecification::insert($rows);
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
