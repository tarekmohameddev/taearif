<?php

namespace App\Imports;

use App\Models\ApiCustomer;
use App\Models\ApiCustomerPropertyInterested;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Property;
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

class CustomersSingleSheetImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors;

    protected $userId;
    protected $limit;

    // Runtime caches for name lookups
    protected $typeNameLookup = [];
    protected $priorityNameLookup = [];
    protected $stageNameLookup = [];
    protected $procedureNameLookup = [];
    protected $cityNameLookup = [];
    protected $districtNameLookup = [];

    public $importedCount = 0;

    // Cache TTL in seconds (1 hour)
    private const CACHE_TTL = 3600;

    public function __construct($userId, $limit = 1000)
    {
        $this->userId = $userId;
        $this->limit = $limit;

        // Pre-load lookups for this tenant
        $this->loadLookups();
    }

    /**
     * Limit the number of rows to read from Excel
     */
    public function limit(): int
    {
        return $this->limit;
    }

    /**
     * Pre-load all lookup tables for this tenant
     */
    protected function loadLookups(): void
    {
        // Load types
        $types = UserApiCustomerType::where('user_id', $this->userId)->get(['id', 'name']);
        foreach ($types as $type) {
            $normalized = $this->normalizeName($type->name);
            if ($normalized) {
                $this->typeNameLookup[$normalized] = $type->id;
            }
        }

        // Load priorities
        $priorities = UserApiCustomerPriority::where('user_id', $this->userId)->get(['id', 'name']);
        foreach ($priorities as $priority) {
            $normalized = $this->normalizeName($priority->name);
            if ($normalized) {
                $this->priorityNameLookup[$normalized] = $priority->id;
            }
        }

        // Load stages
        $stages = UserApiCustomerStage::where('user_id', $this->userId)->get(['id', 'stage_name']);
        foreach ($stages as $stage) {
            $normalized = $this->normalizeName($stage->stage_name);
            if ($normalized) {
                $this->stageNameLookup[$normalized] = $stage->id;
            }
        }

        // Load procedures
        $procedures = UserApiCustomerProcedure::where('user_id', $this->userId)->get(['id', 'procedure_name']);
        foreach ($procedures as $procedure) {
            $normalized = $this->normalizeName($procedure->procedure_name);
            if ($normalized) {
                $this->procedureNameLookup[$normalized] = $procedure->id;
            }
        }

        // Load cities (global, not tenant-specific)
        $cities = UserCity::get(['id', 'name_ar', 'name_en']);
        foreach ($cities as $city) {
            $normalizedAr = $this->normalizeName($city->name_ar);
            $normalizedEn = $this->normalizeName($city->name_en);
            if ($normalizedAr) {
                $this->cityNameLookup[$normalizedAr] = $city->id;
            }
            if ($normalizedEn) {
                $this->cityNameLookup[$normalizedEn] = $city->id;
            }
        }

        // Load districts (global, not tenant-specific)
        $districts = UserDistrict::get(['id', 'name_ar', 'name_en', 'city_id']);
        foreach ($districts as $district) {
            $normalizedAr = $this->normalizeName($district->name_ar);
            $normalizedEn = $this->normalizeName($district->name_en);
            if ($normalizedAr) {
                if (!isset($this->districtNameLookup[$normalizedAr])) {
                    $this->districtNameLookup[$normalizedAr] = [];
                }
                $this->districtNameLookup[$normalizedAr][] = [
                    'id' => $district->id,
                    'city_id' => $district->city_id,
                ];
            }
            if ($normalizedEn) {
                if (!isset($this->districtNameLookup[$normalizedEn])) {
                    $this->districtNameLookup[$normalizedEn] = [];
                }
                $this->districtNameLookup[$normalizedEn][] = [
                    'id' => $district->id,
                    'city_id' => $district->city_id,
                ];
            }
        }
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row = $row->toArray();

        // Skip completely empty rows
        $hasData = !empty(array_filter($row, function ($value) {
            return !is_null($value) && $value !== '';
        }));

        if (!$hasData) {
            return;
        }

        // Skip rows marked by prepareForValidation
        if (isset($row['_skip_empty_row']) && $row['_skip_empty_row'] === true) {
            return;
        }

        // Resolve lookup fields (name -> ID)
        $typeId = $this->resolveType($row, $rowIndex);
        $priorityId = $this->resolvePriority($row, $rowIndex);
        $stageId = $this->resolveStage($row, $rowIndex);
        $procedureId = $this->resolveProcedure($row, $rowIndex);
        [$cityId, $districtId] = $this->resolveLocation($row, $rowIndex);

        // Wrap entire row processing in transaction
        DB::transaction(function () use ($row, $rowIndex, $typeId, $priorityId, $stageId, $procedureId, $cityId, $districtId) {
            $customerData = [
                'user_id' => $this->userId,
                'name' => $row['name'] ?? null,
                'email' => !empty($row['email']) ? trim($row['email']) : null,
                'phone_number' => !empty($row['phone_number']) ? trim($row['phone_number']) : null,
                'note' => $row['note'] ?? null,
                'type_id' => $typeId,
                'priority_id' => $priorityId,
                'stage_id' => $stageId,
                'procedure_id' => $procedureId,
                'city_id' => $cityId,
                'district_id' => $districtId,
            ];

            // Handle password - use provided or default to '12345678'
            $customerData['password'] = bcrypt($row['password'] ?? '12345678');

            $customer = ApiCustomer::create($customerData);

            // Handle interested categories/properties (IDs, comma-separated)
            $categoryIds = $this->parseIdList($row['interested_category_ids'] ?? null);
            $propertyIds = $this->parseIdList($row['interested_property_ids'] ?? null);

            if (!empty($categoryIds)) {
                $validCategoryIds = ApiUserCategory::whereIn('id', $categoryIds)->pluck('id')->all();
                $inserts = [];
                foreach ($validCategoryIds as $cid) {
                    $inserts[] = [
                        'user_id' => $this->userId,
                        'customer_id' => $customer->id,
                        'category_id' => $cid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($inserts)) {
                    ApiCustomerPropertyInterested::insert($inserts);
                }
            }

            if (!empty($propertyIds)) {
                $validPropertyIds = Property::where('user_id', $this->userId)
                    ->whereIn('id', $propertyIds)
                    ->pluck('id')
                    ->all();

                $inserts = [];
                foreach ($validPropertyIds as $pid) {
                    $inserts[] = [
                        'user_id' => $this->userId,
                        'customer_id' => $customer->id,
                        'property_id' => $pid,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                if (!empty($inserts)) {
                    ApiCustomerPropertyInterested::insert($inserts);
                }
            }

            $this->importedCount++;
        });
    }

    /**
     * Resolve type from type_name or type_id
     */
    protected function resolveType(array $row, int $rowIndex): ?int
    {
        // First check if type_id is provided directly
        if (!empty($row['type_id']) && is_numeric($row['type_id'])) {
            $typeId = (int) $row['type_id'];
            // Validate it exists for this user
            if (UserApiCustomerType::where('user_id', $this->userId)->where('id', $typeId)->exists()) {
                return $typeId;
            }
            throw new \Exception("Row {$rowIndex}: Invalid type_id: {$row['type_id']}. Type does not exist for this tenant.");
        }

        // Then check type_name
        if (!empty($row['type_name'])) {
            $normalized = $this->normalizeName($row['type_name']);
            if ($normalized && isset($this->typeNameLookup[$normalized])) {
                return $this->typeNameLookup[$normalized];
            }
            throw new \Exception("Row {$rowIndex}: Unknown type_name: '{$row['type_name']}'. Please check the lookup sheet for valid types.");
        }

        return null;
    }

    /**
     * Resolve priority from priority_name or priority_id
     */
    protected function resolvePriority(array $row, int $rowIndex): ?int
    {
        if (!empty($row['priority_id']) && is_numeric($row['priority_id'])) {
            $priorityId = (int) $row['priority_id'];
            if (UserApiCustomerPriority::where('user_id', $this->userId)->where('id', $priorityId)->exists()) {
                return $priorityId;
            }
            throw new \Exception("Row {$rowIndex}: Invalid priority_id: {$row['priority_id']}. Priority does not exist for this tenant.");
        }

        if (!empty($row['priority_name'])) {
            $normalized = $this->normalizeName($row['priority_name']);
            if ($normalized && isset($this->priorityNameLookup[$normalized])) {
                return $this->priorityNameLookup[$normalized];
            }
            throw new \Exception("Row {$rowIndex}: Unknown priority_name: '{$row['priority_name']}'. Please check the lookup sheet for valid priorities.");
        }

        return null;
    }

    /**
     * Resolve stage from stage_name or stage_id
     */
    protected function resolveStage(array $row, int $rowIndex): ?int
    {
        if (!empty($row['stage_id']) && is_numeric($row['stage_id'])) {
            $stageId = (int) $row['stage_id'];
            if (UserApiCustomerStage::where('user_id', $this->userId)->where('id', $stageId)->exists()) {
                return $stageId;
            }
            throw new \Exception("Row {$rowIndex}: Invalid stage_id: {$row['stage_id']}. Stage does not exist for this tenant.");
        }

        if (!empty($row['stage_name'])) {
            $normalized = $this->normalizeName($row['stage_name']);
            if ($normalized && isset($this->stageNameLookup[$normalized])) {
                return $this->stageNameLookup[$normalized];
            }
            throw new \Exception("Row {$rowIndex}: Unknown stage_name: '{$row['stage_name']}'. Please check the lookup sheet for valid stages.");
        }

        return null;
    }

    /**
     * Resolve procedure from procedure_name or procedure_id
     */
    protected function resolveProcedure(array $row, int $rowIndex): ?int
    {
        if (!empty($row['procedure_id']) && is_numeric($row['procedure_id'])) {
            $procedureId = (int) $row['procedure_id'];
            if (UserApiCustomerProcedure::where('user_id', $this->userId)->where('id', $procedureId)->exists()) {
                return $procedureId;
            }
            throw new \Exception("Row {$rowIndex}: Invalid procedure_id: {$row['procedure_id']}. Procedure does not exist for this tenant.");
        }

        if (!empty($row['procedure_name'])) {
            $normalized = $this->normalizeName($row['procedure_name']);
            if ($normalized && isset($this->procedureNameLookup[$normalized])) {
                return $this->procedureNameLookup[$normalized];
            }
            throw new \Exception("Row {$rowIndex}: Unknown procedure_name: '{$row['procedure_name']}'. Please check the lookup sheet for valid procedures.");
        }

        return null;
    }

    /**
     * Resolve city and district from names or IDs
     */
    protected function resolveLocation(array $row, int $rowIndex): array
    {
        $cityId = null;
        $districtId = null;

        // Resolve city
        if (!empty($row['city_id']) && is_numeric($row['city_id'])) {
            $cityId = (int) $row['city_id'];
            if (!UserCity::where('id', $cityId)->exists()) {
                throw new \Exception("Row {$rowIndex}: Invalid city_id: {$row['city_id']}. City does not exist.");
            }
        } elseif (!empty($row['city_name'])) {
            $normalized = $this->normalizeName($row['city_name']);
            if ($normalized && isset($this->cityNameLookup[$normalized])) {
                $cityId = $this->cityNameLookup[$normalized];
            } else {
                throw new \Exception("Row {$rowIndex}: Unknown city_name: '{$row['city_name']}'. Please check the lookup sheet for valid cities.");
            }
        }

        // Resolve district
        if (!empty($row['district_id']) && is_numeric($row['district_id'])) {
            $districtId = (int) $row['district_id'];
            $district = UserDistrict::find($districtId);
            if (!$district) {
                throw new \Exception("Row {$rowIndex}: Invalid district_id: {$row['district_id']}. District does not exist.");
            }
            // Validate district belongs to city if city is specified
            if ($cityId && $district->city_id !== $cityId) {
                throw new \Exception("Row {$rowIndex}: District ID {$districtId} does not belong to the specified city (city_id: {$cityId}).");
            }
        } elseif (!empty($row['district_name'])) {
            $normalized = $this->normalizeName($row['district_name']);
            if ($normalized && isset($this->districtNameLookup[$normalized])) {
                $matches = $this->districtNameLookup[$normalized];

                // If city is specified, filter by city
                if ($cityId) {
                    $matches = array_filter($matches, fn($m) => $m['city_id'] === $cityId);
                    $matches = array_values($matches);
                }

                if (count($matches) === 1) {
                    $districtId = $matches[0]['id'];
                } elseif (count($matches) > 1) {
                    throw new \Exception("Row {$rowIndex}: Multiple districts named '{$row['district_name']}' exist. Please specify city_name or use district_id.");
                } else {
                    throw new \Exception("Row {$rowIndex}: District '{$row['district_name']}' not found in the specified city.");
                }
            } else {
                throw new \Exception("Row {$rowIndex}: Unknown district_name: '{$row['district_name']}'. Please check the lookup sheet for valid districts.");
            }
        }

        return [$cityId, $districtId];
    }

    /**
     * Normalize name for lookup comparison
     */
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
            '/[أإآ]/u' => 'ا',
            '/ة/u' => 'ه',
            '/ى/u' => 'ي',
            '/[\x{064B}-\x{065F}]/u' => '',
            '/\s+/u' => ' ',
        ];

        $normalized = preg_replace(array_keys($patterns), array_values($patterns), $value);

        return Str::lower($normalized);
    }

    public function rules(): array
    {
        // All fields are optional
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'type_name' => 'nullable|string|max:255',
            'type_id' => 'nullable|integer',
            'priority_name' => 'nullable|string|max:255',
            'priority_id' => 'nullable|integer',
            'stage_name' => 'nullable|string|max:255',
            'stage_id' => 'nullable|integer',
            'procedure_name' => 'nullable|string|max:255',
            'procedure_id' => 'nullable|integer',
            'city_name' => 'nullable|string|max:255',
            'city_id' => 'nullable|integer',
            'district_name' => 'nullable|string|max:255',
            'district_id' => 'nullable|integer',
            'password' => 'nullable|string|min:6',
            'interested_category_ids' => 'nullable|string',
            'interested_property_ids' => 'nullable|string',
        ];
    }

    /**
     * Prepare data for validation - skip empty rows before validation runs
     */
    public function prepareForValidation($data, $index)
    {
        // Check if row is completely empty
        $hasData = !empty(array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        }));

        if (!$hasData) {
            $data['_skip_empty_row'] = true;
        }

        return $data;
    }

    /**
     * Parse comma-separated IDs into an integer array.
     */
    protected function parseIdList($value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            return [(int) $value];
        }

        if (is_string($value)) {
            return array_values(array_filter(array_map('intval', explode(',', $value))));
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }

        return [];
    }
}











