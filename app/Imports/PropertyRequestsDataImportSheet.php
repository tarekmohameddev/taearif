<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Models\Api\UserPropertyRequest;
use App\Models\ApiCustomer;
use App\Models\PropertyRequestStatus;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
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

class PropertyRequestsDataImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    protected ?int $tenantId = null;

    /** @var array<string, int> */
    protected array $districtNameLookup = [];

    /** @var array<string, int> */
    protected array $statusNameLookup = [];

    protected int $unresolvedEmployees = 0;

    public function __construct(
        protected int $ownerId,
        protected IdRemapper $remap,
        protected bool $updateExisting = false,
        protected int $limit = 5000,
    ) {
        $user = User::find($ownerId);
        if ($user) {
            $this->tenantId = $user->tenantOwnerId();
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
                $customerId = $this->resolveCustomerId($row);
                $projectId = $this->resolveProjectId($row);
                $initialPropertyId = $this->resolvePropertyId($row['initial_property_id'] ?? null);
                $propertyIds = $this->resolvePropertyIdsList($row['property_ids'] ?? null);
                $districtsId = $this->resolveDistrictId($row, $rowIndex);
                $statusId = $this->resolveStatusId($row);
                $cityId = $this->resolveCityId($row['city_id'] ?? null);
                $categoryId = $this->resolveCategoryId($row['category_id'] ?? null);
                $responsibleEmployeeId = $this->resolveEmployeeId($row['responsible_employee_id'] ?? null);

                $detectedEntities = $this->normalizeDetectedEntitiesJson($row['detected_entities_json'] ?? null);

                $sourceId = (int) ($row['id'] ?? 0);
                $existing = $sourceId
                    ? UserPropertyRequest::where('user_id', $this->ownerId)->where('id', $sourceId)->first()
                    : null;

                if ($existing && !$this->updateExisting) {
                    $this->skipped++;

                    return;
                }

                $attributes = [
                    'user_id' => $this->ownerId,
                    'customer_id' => $customerId,
                    'source' => $row['source'] ?? null,
                    'referral_source' => $row['referral_source'] ?? null,
                    'region' => $row['region'] ?? null,
                    'purpose' => $row['purpose'] ?? null,
                    'property_type' => $row['property_type'] ?? null,
                    'category_id' => $categoryId,
                    'city_id' => $cityId,
                    'districts_id' => $districtsId,
                    'area_from' => $row['area_from'] ?? null,
                    'area_to' => $row['area_to'] ?? null,
                    'purchase_method' => $row['purchase_method'] ?? null,
                    'budget_from' => $row['budget_from'] ?? null,
                    'budget_to' => $row['budget_to'] ?? null,
                    'seriousness' => $row['seriousness'] ?? null,
                    'purchase_goal' => $row['purchase_goal'] ?? null,
                    'wants_similar_offers' => $this->toBool($row['wants_similar_offers'] ?? null),
                    'full_name' => $row['full_name'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'contact_on_whatsapp' => $this->toBool($row['contact_on_whatsapp'] ?? null),
                    'notes' => $row['notes'] ?? null,
                    'is_read' => $this->toBool($row['is_read'] ?? null),
                    'is_archived' => $this->toBool($row['is_archived'] ?? null),
                    'is_ignored' => $this->toBool($row['is_ignored'] ?? null),
                    'is_active' => $this->toBool($row['is_active'] ?? true),
                    'status_id' => $statusId,
                    'customers_hub_stage_id' => $row['customers_hub_stage_id'] ?? null,
                    'property_ids' => $propertyIds,
                    'initial_property_id' => $initialPropertyId,
                    'project_id' => $projectId,
                    'responsible_employee_id' => $responsibleEmployeeId,
                    'inquiry_type' => $row['inquiry_type'] ?? null,
                    'currency' => $row['currency'] ?? null,
                    'bedrooms' => $row['bedrooms'] ?? null,
                    'bathrooms' => $row['bathrooms'] ?? null,
                    'furnished' => $row['furnished'] ?? null,
                    'location' => $row['location'] ?? null,
                    'country_code' => $row['country_code'] ?? null,
                    'region_code' => $row['region_code'] ?? null,
                    'city' => $row['city'] ?? null,
                    'district' => $row['district'] ?? null,
                    'latitude' => $row['latitude'] ?? null,
                    'longitude' => $row['longitude'] ?? null,
                    'location_confidence' => $row['location_confidence'] ?? null,
                    'lang' => $row['lang'] ?? null,
                    'detected_entities_json' => $detectedEntities,
                ];

                if ($existing) {
                    unset($attributes['user_id']);
                    $existing->update($attributes);
                    $this->updated++;
                } else {
                    UserPropertyRequest::create($attributes);
                    $this->imported++;
                }
            });
        } catch (\Throwable $e) {
            $this->recordError($rowIndex, $e->getMessage());
            $this->skipped++;
        }
    }

    public function rules(): array
    {
        return [
            'customer_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:255',
            // Align with TenantDataImportService required_headers: full_name, phone.
            // Maatwebsite validates as [rowIndex => row]; use *._skip_empty_row so exclude_if
            // resolves against the same nested row (plain _skip_empty_row never matches).
            'full_name' => 'exclude_if:*._skip_empty_row,1|required|string|max:255',
            'phone' => 'exclude_if:*._skip_empty_row,1|required|string|max:50',
            'project_id' => 'nullable|integer',
            'initial_property_id' => 'nullable|integer',
            'property_ids' => 'nullable|string',
            'status_id' => 'nullable|integer',
            'status_name' => 'nullable|string|max:255',
            'district_name' => 'nullable|string|max:255',
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
        foreach (UserDistrict::get(['id', 'name_ar', 'name_en']) as $district) {
            foreach (['name_ar', 'name_en'] as $field) {
                $normalized = $this->normalizeName($district->{$field});
                if ($normalized) {
                    $this->districtNameLookup[$normalized] = $district->id;
                }
            }
        }

        foreach (PropertyRequestStatus::forTenant($this->ownerId)->get(['id', 'name_ar', 'name_en']) as $status) {
            foreach (['name_ar', 'name_en'] as $field) {
                $normalized = $this->normalizeName($status->{$field});
                if ($normalized) {
                    $this->statusNameLookup[$normalized] = $status->id;
                }
            }
        }
    }

    protected function resolveCustomerId(array $row): ?int
    {
        $customerId = $this->remap->get('customer', $row['customer_id'] ?? null);
        if ($customerId && ApiCustomer::where('user_id', $this->ownerId)->where('id', $customerId)->exists()) {
            return $customerId;
        }

        $name = $row['customer_name'] ?? $row['full_name'] ?? null;
        $phone = $row['phone'] ?? null;

        if ($name === null && $phone === null) {
            return null;
        }

        $query = ApiCustomer::where('user_id', $this->ownerId);

        if ($phone !== null && $phone !== '') {
            $query->where('phone_number', trim((string) $phone));
        }

        if ($name !== null && $name !== '') {
            $query->where('name', trim((string) $name));
        }

        return $query->value('id');
    }

    protected function resolveProjectId(array $row): ?int
    {
        $projectId = $this->remap->get('project', $row['project_id'] ?? null);
        if ($projectId && Project::where('user_id', $this->ownerId)->where('id', $projectId)->exists()) {
            return $projectId;
        }

        return null;
    }

    protected function resolvePropertyId(mixed $oldId): ?int
    {
        $propertyId = $this->remap->get('property', $oldId);
        if ($propertyId && Property::where('user_id', $this->ownerId)->where('id', $propertyId)->exists()) {
            return $propertyId;
        }

        return null;
    }

    /**
     * @return list<int>
     */
    protected function resolvePropertyIdsList(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        try {
            // Accept JSON arrays without throwing on malformed input.
            if (is_array($raw)) {
                $candidates = $raw;
            } elseif (is_string($raw)) {
                $trimmed = trim($raw);
                if ($trimmed === '') {
                    return [];
                }
                if (str_starts_with($trimmed, '[') || str_starts_with($trimmed, '{')) {
                    $decoded = json_decode($trimmed, true);
                    $candidates = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                        ? $decoded
                        : array_filter(array_map('trim', explode(',', $trimmed)));
                } else {
                    $candidates = array_filter(array_map('trim', explode(',', $trimmed)));
                }
            } else {
                $candidates = array_filter(array_map('trim', explode(',', (string) $raw)));
            }

            $ids = [];
            foreach ($candidates as $oldId) {
                if (is_array($oldId)) {
                    continue;
                }
                $newId = $this->resolvePropertyId($oldId);
                if ($newId !== null) {
                    $ids[] = $newId;
                }
            }

            return $ids;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Never throw on malformed detected_entities_json — keep raw string or null.
     */
    protected function normalizeDetectedEntitiesJson(mixed $detectedEntities): ?string
    {
        try {
            if ($detectedEntities === null || $detectedEntities === '') {
                return null;
            }

            if (is_string($detectedEntities)) {
                $decoded = json_decode($detectedEntities, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return json_encode($decoded, JSON_UNESCAPED_UNICODE);
                }

                // Malformed JSON: store as-is rather than aborting the row.
                return $detectedEntities;
            }

            if (is_array($detectedEntities)) {
                return json_encode($detectedEntities, JSON_UNESCAPED_UNICODE) ?: null;
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    protected function resolveDistrictId(array $row, int $rowIndex): ?int
    {
        $districtIdRaw = $row['districts_id'] ?? null;
        if ($districtIdRaw !== null && $districtIdRaw !== '' && is_numeric($districtIdRaw)) {
            $districtId = (int) $districtIdRaw;
            if (UserDistrict::where('id', $districtId)->exists()) {
                return $districtId;
            }
        }

        $districtName = $row['district_name'] ?? null;
        if ($districtName !== null && $districtName !== '') {
            $normalized = $this->normalizeName($districtName);
            if ($normalized && isset($this->districtNameLookup[$normalized])) {
                return $this->districtNameLookup[$normalized];
            }
        }

        return null;
    }

    protected function resolveStatusId(array $row): ?int
    {
        $statusIdRaw = $row['status_id'] ?? null;
        if ($statusIdRaw !== null && $statusIdRaw !== '' && is_numeric($statusIdRaw)) {
            $statusId = (int) $statusIdRaw;
            if (PropertyRequestStatus::forTenant($this->ownerId)->where('id', $statusId)->exists()) {
                return $statusId;
            }
        }

        $statusName = $row['status_name'] ?? null;
        if ($statusName !== null && $statusName !== '') {
            $normalized = $this->normalizeName($statusName);
            if ($normalized && isset($this->statusNameLookup[$normalized])) {
                return $this->statusNameLookup[$normalized];
            }
        }

        return null;
    }

    protected function resolveCityId(mixed $cityIdRaw): ?int
    {
        if ($cityIdRaw === null || $cityIdRaw === '' || !is_numeric($cityIdRaw)) {
            return null;
        }

        $cityId = (int) $cityIdRaw;

        return UserCity::where('id', $cityId)->exists() ? $cityId : null;
    }

    protected function resolveCategoryId(mixed $categoryIdRaw): ?int
    {
        if ($categoryIdRaw === null || $categoryIdRaw === '' || !is_numeric($categoryIdRaw)) {
            return null;
        }

        $categoryId = (int) $categoryIdRaw;

        return \App\Models\User\RealestateManagement\ApiUserCategory::where('id', $categoryId)
            ->exists()
            ? $categoryId
            : null;
    }

    protected function resolveEmployeeId(mixed $employeeIdRaw): ?int
    {
        if ($employeeIdRaw === null || $employeeIdRaw === '' || !is_numeric($employeeIdRaw)) {
            return null;
        }

        if (!$this->tenantId) {
            $this->unresolvedEmployees++;

            return null;
        }

        $employeeId = (int) $employeeIdRaw;

        $exists = User::where('id', $employeeId)
            ->where('tenant_id', $this->tenantId)
            ->where('account_type', 'employee')
            ->where('active', true)
            ->exists();

        if (!$exists) {
            $this->unresolvedEmployees++;

            return null;
        }

        return $employeeId;
    }

    protected function flushLookupDropWarnings(): void
    {
        if ($this->unresolvedEmployees > 0) {
            $this->recordWarning(
                null,
                "{$this->unresolvedEmployees} employees not found for this tenant and were skipped"
            );
            $this->unresolvedEmployees = 0;
        }
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if ($value === null || $value === '') {
            return false;
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
