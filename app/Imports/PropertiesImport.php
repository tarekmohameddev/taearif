<?php

namespace App\Imports;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertySpecification;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\Language;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PropertiesImport implements OnEachRow, WithHeadingRow, WithValidation
{
    protected $userId;
    protected $defaultLanguageId;

    public function __construct($userId)
    {
        $this->userId = $userId;
        $this->defaultLanguageId = Language::where('user_id', $userId)
            ->where('is_default', 1)
            ->value('id');
        
        // Require default language for bulk import
        if (!$this->defaultLanguageId) {
            throw new \Exception('No default language configured for user. Please set a default language before importing properties.');
        }
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        // Parse new relational columns
        $galleryImages = $this->parseCommaSeparated($row['gallery_images'] ?? null);
        $amenityIds = $this->parseCommaSeparatedIntegers($row['amenity_ids'] ?? null);
        $specifications = $this->parseSpecifications($row['specifications'] ?? null);

        // Log parsing issues for debugging
        if (!empty($row['gallery_images']) && empty($galleryImages)) {
            Log::warning("Row {$rowIndex}: Failed to parse gallery_images", ['value' => $row['gallery_images']]);
        }
        if (!empty($row['amenity_ids']) && empty($amenityIds)) {
            Log::warning("Row {$rowIndex}: Failed to parse amenity_ids", ['value' => $row['amenity_ids']]);
        }

        // Wrap entire row processing in transaction
        DB::transaction(function () use ($row, $rowIndex, $galleryImages, $amenityIds, $specifications) {
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
                'features'        => [],
                'region_id'       => $row['region_id'] ?? null,
                'city_id'         => $row['city_id'] ?? null,
                'category_id'     => null,
                'show_reservations' => true,
            ];

            // Images (Expect URLs)
            $featuredImage = $row['featured_image'] ?? null;
            $videoImage    = null;
            $floorPlans    = null;
            $featured      = 0;

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
            $contentData = [
                'language_id'      => $this->defaultLanguageId,
                'title'            => $row['title'],
                'slug'             => Str::slug($row['title']) . '-' . uniqid(),
                'address'          => $row['address'],
                'description'      => $row['description'],
                'meta_keyword'     => null,
                'meta_description' => Str::limit($row['description'], 150),
                'category_id'      => $property->category_id,
                'city_id'          => $row['city_id'] ?? null,
                'state_id'         => null,
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

            // Process amenities
            foreach ($amenityIds as $amenityId) {
                // Verify amenity exists for this user
                $amenityExists = Amenity::where('id', $amenityId)
                    ->where('user_id', $this->userId)
                    ->exists();
                
                if (!$amenityExists) {
                    throw new \Exception("Row {$rowIndex}: Amenity ID {$amenityId} does not exist or does not belong to this user.");
                }

                PropertyAmenity::sotreAmenity($this->userId, $property->id, $amenityId);
            }

            // Process specifications
            foreach ($specifications as $spec) {
                $specData = [
                    'language_id' => $this->defaultLanguageId,
                    'key' => $spec['key'],
                    'label' => $spec['label'] ?? $spec['key'],
                    'value' => $spec['value'],
                ];
                PropertySpecification::storeSpecification($this->userId, $property->id, $specData);
            }
        });
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
     * Parse specifications from JSON string or return empty array
     * Expected format: [{"key": "bedrooms", "label": "Bedrooms", "value": "3"}, ...]
     */
    protected function parseSpecifications(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

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

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'price'       => 'required|numeric',
            'address'     => 'required|string',
            'description' => 'required|string',
            'purpose'     => 'required|in:sale,rent',
            'type'        => 'required|in:residential,commercial',
            'area'        => 'required|numeric',
            'region_id'   => 'nullable|integer',
            'city_id'     => 'nullable|integer',
            'featured_image' => 'nullable|url',
            'video_url'   => 'nullable|url',
            'gallery_images' => 'nullable|string',
            'amenity_ids' => ['nullable', 'string', 'regex:/^(\d+)(,\s*\d+)*$/'],
            'specifications' => 'nullable|json',
        ];
    }
}
