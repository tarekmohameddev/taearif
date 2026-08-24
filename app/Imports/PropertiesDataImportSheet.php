<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Imports\Support\MissingDefaultLanguageException;
use App\Models\User\Language;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertySpecification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

class PropertiesDataImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    protected int $defaultLanguageId;

    /** @var array<string, int> */
    protected array $cityNameLookup = [];

    /** @var array<string, list<array{id: int, city_id: int|null}>> */
    protected array $districtNameLookup = [];

    /** @var array<string, int> */
    protected array $amenityNameLookup = [];

    protected int $unresolvedAmenities = 0;

    public function __construct(
        protected int $ownerId,
        protected IdRemapper $remap,
        protected bool $updateExisting = false,
        protected int $limit = 5000,
    ) {
        $this->defaultLanguageId = (int) Language::where('user_id', $ownerId)
            ->where('is_default', 1)
            ->value('id');

        if (!$this->defaultLanguageId) {
            throw new MissingDefaultLanguageException('properties');
        }

        $this->loadLookups();
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function summary(): array
    {
        $this->flushLookupDropWarnings();
        $this->appendValidationFailures();

        return [
            'imported' => $this->imported,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->importErrors,
            'warnings' => $this->warnings,
        ];
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $row = $row->toArray();

        if (!empty($row['_skip_empty_row'])) {
            return;
        }

        try {
            DB::transaction(function () use ($row, $rowIndex) {
                $cityId = $this->resolveCityId($row, $rowIndex);
                $stateId = $this->resolveDistrictId($row['district_name'] ?? null, $cityId, $rowIndex);

                $projectId = $this->remap->get('project', $row['project_id'] ?? null);

                $sourceId = (int) ($row['id'] ?? 0);
                $existing = $sourceId
                    ? Property::where('user_id', $this->ownerId)->where('id', $sourceId)->first()
                    : null;

                if ($existing && !$this->updateExisting) {
                    $this->remap->put('property', $sourceId, $existing->id);
                    $this->skipped++;

                    return;
                }

                $attributes = [
                    'project_id' => $projectId,
                    'price' => $row['price'] ?? null,
                    'purpose' => $row['purpose'] ?? null,
                    'property_type' => $row['property_type'] ?? null,
                    'area' => $row['area'] ?? null,
                    'beds' => $row['beds'] ?? null,
                    'bath' => $row['bath'] ?? null,
                    'status' => $row['status'] ?? 1,
                    'featured' => $this->toBool($row['featured'] ?? null) ? 1 : 0,
                    'payment_method' => $row['payment_method'] ?? null,
                    'video_url' => $row['video_url'] ?? null,
                    'latitude' => $row['latitude'] ?? null,
                    'longitude' => $row['longitude'] ?? null,
                    'deed_number' => $row['deed_number'] ?? null,
                    'owner_number' => $row['owner_number'] ?? null,
                    'water_meter_number' => $row['water_meter_number'] ?? null,
                    'electricity_meter_number' => $row['electricity_meter_number'] ?? null,
                    'advertising_license' => $row['advertising_license'] ?? null,
                    'featured_image' => $this->relPath($row['featured_image_url'] ?? null),
                ];

                if ($existing) {
                    $existing->update($attributes);
                    $property = $existing;
                    // Refresh children so an update doesn't duplicate them.
                    PropertyContent::where('property_id', $property->id)->delete();
                    PropertySliderImg::where('property_id', $property->id)->delete();
                    PropertyAmenity::where('property_id', $property->id)->delete();
                    PropertySpecification::where('property_id', $property->id)->delete();
                    $this->updated++;
                } else {
                    $property = Property::create($attributes + [
                        'user_id' => $this->ownerId,
                        'created_by' => auth()->id() ?? $this->ownerId,
                    ]);
                    $this->imported++;
                }

                PropertyContent::storePropertyContent($this->ownerId, $property->id, [
                    'language_id' => $this->defaultLanguageId,
                    'title' => $row['title'] ?? '',
                    'address' => $row['address'] ?? '',
                    'description' => $row['description'] ?? '',
                    'category_id' => null,
                    'country_id' => null,
                    'city_id' => $cityId,
                    'state_id' => $stateId,
                ]);

                $galleryPaths = $this->splitImageUrls($row['gallery_image_urls'] ?? null);
                if ($galleryPaths !== []) {
                    $now = now();
                    $rows = [];
                    foreach ($galleryPaths as $path) {
                        $rows[] = [
                            'user_id' => $this->ownerId,
                            'property_id' => $property->id,
                            'image' => $path,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    PropertySliderImg::insert($rows);
                }

                $this->importAmenities($property->id, $row['amenities'] ?? null);
                $this->importSpecifications($property->id, $row['specifications'] ?? null);

                $this->remap->put('property', $sourceId, $property->id);
            });
        } catch (\Throwable $e) {
            $this->recordError($rowIndex, $e->getMessage());
            $this->skipped++;
        }
    }

    public function rules(): array
    {
        return [
            // Align with TenantDataImportService required_headers: title, price.
            // Maatwebsite validates as [rowIndex => row]; use *._skip_empty_row so exclude_if
            // resolves against the same nested row (plain _skip_empty_row never matches).
            'title' => 'exclude_if:*._skip_empty_row,1|required|string|max:255',
            'price' => 'exclude_if:*._skip_empty_row,1|required|numeric',
            'purpose' => 'nullable|string|max:50',
            'property_type' => 'nullable|string|max:50',
            'city_name_ar' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer',
        ];
    }

    public function prepareForValidation($data, $index): array
    {
        $data = is_array($data) ? $data : (array) $data;

        // Excel returns numeric-looking cells (phone numbers, ids) as int/float;
        // stringify them so `string`/`max` validation rules don't wrongly reject the row.
        foreach ($data as $key => $value) {
            if (is_int($value) || is_float($value)) {
                $data[$key] = (string) $value;
            }
        }

        $hasData = !empty(array_filter($data, fn ($v) => !is_null($v) && $v !== ''));
        if (!$hasData) {
            // Must be 1/"1" — boolean true does not satisfy exclude_if:...,1 (strict compare).
            $data['_skip_empty_row'] = 1;
        }

        return $data;
    }

    protected function loadLookups(): void
    {
        foreach (UserCity::get(['id', 'name_ar', 'name_en']) as $city) {
            foreach (['name_ar', 'name_en'] as $field) {
                $normalized = $this->normalizeName($city->{$field});
                if ($normalized) {
                    $this->cityNameLookup[$normalized] = $city->id;
                }
            }
        }

        foreach (UserDistrict::get(['id', 'name_ar', 'name_en', 'city_id']) as $district) {
            foreach (['name_ar', 'name_en'] as $field) {
                $normalized = $this->normalizeName($district->{$field});
                if (!$normalized) {
                    continue;
                }
                $this->districtNameLookup[$normalized][] = [
                    'id' => $district->id,
                    'city_id' => $district->city_id,
                ];
            }
        }

        foreach (Amenity::where('user_id', $this->ownerId)->get(['id', 'name']) as $amenity) {
            $normalized = $this->normalizeName($amenity->name);
            if ($normalized) {
                $this->amenityNameLookup[$normalized] = $amenity->id;
            }
        }
    }

    protected function resolveCityId(array $row, int $rowIndex): ?int
    {
        $cityName = $row['city_name_ar'] ?? null;
        if ($cityName === null || $cityName === '') {
            return null;
        }

        $normalized = $this->normalizeName($cityName);
        if ($normalized && isset($this->cityNameLookup[$normalized])) {
            return $this->cityNameLookup[$normalized];
        }

        throw new \RuntimeException("Row {$rowIndex}: Unknown city_name_ar: '{$cityName}'.");
    }

    protected function resolveDistrictId(?string $districtName, ?int $cityId, int $rowIndex): ?int
    {
        if ($districtName === null || $districtName === '') {
            return null;
        }

        $normalized = $this->normalizeName($districtName);
        if (!$normalized || !isset($this->districtNameLookup[$normalized])) {
            return null;
        }

        $matches = $this->districtNameLookup[$normalized];
        if ($cityId !== null) {
            $matches = array_values(array_filter($matches, fn ($m) => (int) $m['city_id'] === $cityId));
        }

        if (count($matches) === 1) {
            return $matches[0]['id'];
        }

        if (count($matches) > 1) {
            throw new \RuntimeException(
                "Row {$rowIndex}: Multiple districts named '{$districtName}' exist. Please verify city_name_ar."
            );
        }

        return null;
    }

    protected function importAmenities(int $propertyId, mixed $amenitiesRaw): void
    {
        if ($amenitiesRaw === null || $amenitiesRaw === '') {
            return;
        }

        $names = array_filter(array_map('trim', explode(',', (string) $amenitiesRaw)));
        if ($names === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($names as $name) {
            $normalized = $this->normalizeName($name);
            if (!$normalized || !isset($this->amenityNameLookup[$normalized])) {
                $this->unresolvedAmenities++;
                continue;
            }

            $rows[] = [
                'user_id' => $this->ownerId,
                'property_id' => $propertyId,
                'amenity_id' => $this->amenityNameLookup[$normalized],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            PropertyAmenity::insert($rows);
        }
    }

    protected function flushLookupDropWarnings(): void
    {
        if ($this->unresolvedAmenities > 0) {
            $this->recordWarning(
                null,
                "{$this->unresolvedAmenities} amenities not found for this tenant and were skipped"
            );
            $this->unresolvedAmenities = 0;
        }
    }

    protected function importSpecifications(int $propertyId, mixed $specificationsRaw): void
    {
        if ($specificationsRaw === null || $specificationsRaw === '') {
            return;
        }

        $parts = explode(' | ', (string) $specificationsRaw);
        $now = now();
        $rows = [];
        $keyIndex = 0;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $colonPos = strpos($part, ':');
            if ($colonPos === false) {
                continue;
            }

            $label = trim(substr($part, 0, $colonPos));
            $value = trim(substr($part, $colonPos + 1));

            if ($label === '') {
                continue;
            }

            $rows[] = [
                'user_id' => $this->ownerId,
                'property_id' => $propertyId,
                'language_id' => $this->defaultLanguageId,
                'key' => $keyIndex++,
                'label' => $label,
                'value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            PropertySpecification::insert($rows);
        }
    }

    /**
     * @return list<string>
     */
    protected function splitImageUrls(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($url) => $this->relPath(trim($url)),
            explode(', ', $value)
        )));
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'نعم'], true);
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

        $patterns = [
            '/[أإآ]/u' => 'ا',
            '/ة/u' => 'ه',
            '/ى/u' => 'ي',
            '/[\x{064B}-\x{065F}]/u' => '',
            '/\s+/u' => ' ',
        ];

        $normalized = preg_replace(array_keys($patterns), array_values($patterns), $value);

        return Str::lower($normalized);
    }
}
