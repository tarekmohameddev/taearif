<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use App\Models\ApiCustomer;
use App\Models\CustomersHub\CustomersHubStage;
use App\Models\User;
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

class CustomersDataImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    protected ?int $tenantId = null;

    /** @var array<string, int> */
    protected array $typeNameLookup = [];

    /** @var array<string, int> */
    protected array $priorityNameLookup = [];

    /** @var array<string, int> */
    protected array $stageNameLookup = [];

    /** @var array<string, int> */
    protected array $procedureNameLookup = [];

    /** @var array<string, int> */
    protected array $cityNameLookup = [];

    /** @var array<string, list<array{id: int, city_id: int|null}>> */
    protected array $districtNameLookup = [];

    /** @var array<string, int> */
    protected array $employeeNameLookup = [];

    /** @var array<string, int> */
    protected array $employeeEmailLookup = [];

    protected int $unresolvedTypes = 0;

    protected int $unresolvedPriorities = 0;

    protected int $unresolvedStages = 0;

    protected int $unresolvedProcedures = 0;

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
                $typeId = $this->resolveType($row);
                $priorityId = $this->resolvePriority($row);
                $stageId = $this->resolveStage($row);
                $procedureId = $this->resolveProcedure($row);
                [$cityId, $districtId] = $this->resolveLocation($row, $rowIndex);
                $responsibleEmployeeId = $this->resolveEmployee($row);

                $hubStageFromFile = isset($row['customers_hub_stage_id'])
                    && $row['customers_hub_stage_id'] !== null
                    && $row['customers_hub_stage_id'] !== ''
                    ? (string) $row['customers_hub_stage_id']
                    : null;

                $customerData = [
                    'user_id' => $this->ownerId,
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
                    'responsible_employee_id' => $responsibleEmployeeId,
                ];

                $customer = $this->findExistingCustomer($customerData);

                if ($customer && $this->updateExisting) {
                    unset($customerData['user_id']);
                    // Only overwrite hub stage when the file provided a value.
                    if ($hubStageFromFile !== null) {
                        $customerData['customers_hub_stage_id'] = $hubStageFromFile;
                    }
                    $customer->update($customerData);
                    $this->updated++;
                } elseif ($customer) {
                    $this->skipped++;
                } else {
                    // C7: default hub stage on CREATE when file did not supply one.
                    $customerData['customers_hub_stage_id'] = $hubStageFromFile
                        ?? CustomersHubStage::getDefaultStageId()
                        ?? 'new_lead';
                    $customerData['password'] = bcrypt('12345678');
                    $customer = ApiCustomer::create($customerData);
                    $this->imported++;

                    // C8: cannot de-dupe on re-import without phone or email.
                    if (empty($customerData['phone_number']) && empty($customerData['email'])) {
                        $this->recordWarning(
                            $rowIndex,
                            'Customer created without phone or email — cannot be de-duplicated on re-import'
                        );
                    }
                }

                $this->remap->put('customer', (int) ($row['id'] ?? 0), $customer->id);
            });
        } catch (\Throwable $e) {
            $this->recordError($rowIndex, $e->getMessage());
            $this->skipped++;
        }
    }

    public function rules(): array
    {
        return [
            // Align with TenantDataImportService required_headers: name, phone_number.
            // Maatwebsite validates as [rowIndex => row]; use *._skip_empty_row so exclude_if
            // resolves against the same nested row (plain _skip_empty_row never matches).
            'name' => 'exclude_if:*._skip_empty_row,1|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'exclude_if:*._skip_empty_row,1|required|string|max:50',
            'note' => 'nullable|string',
            'type_name' => 'nullable|string|max:255',
            'priority_name' => 'nullable|string|max:255',
            'stage_name' => 'nullable|string|max:255',
            'procedure_name' => 'nullable|string|max:255',
            'city_name_ar' => 'nullable|string|max:255',
            'city_name_en' => 'nullable|string|max:255',
            'district_name_ar' => 'nullable|string|max:255',
            'district_name_en' => 'nullable|string|max:255',
            'responsible_employee_name' => 'nullable|string|max:255',
            'responsible_employee_email' => 'nullable|email|max:255',
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

    protected function findExistingCustomer(array $customerData): ?ApiCustomer
    {
        if (!empty($customerData['phone_number'])) {
            $found = ApiCustomer::where('user_id', $this->ownerId)
                ->where('phone_number', $customerData['phone_number'])
                ->first();
            if ($found) {
                return $found;
            }
        }

        if (!empty($customerData['email'])) {
            return ApiCustomer::where('user_id', $this->ownerId)
                ->where('email', $customerData['email'])
                ->first();
        }

        return null;
    }

    protected function loadLookups(): void
    {
        foreach (UserApiCustomerType::where('user_id', $this->ownerId)->get(['id', 'name']) as $type) {
            $normalized = $this->normalizeName($type->name);
            if ($normalized) {
                $this->typeNameLookup[$normalized] = $type->id;
            }
        }

        foreach (UserApiCustomerPriority::where('user_id', $this->ownerId)->get(['id', 'name']) as $priority) {
            $normalized = $this->normalizeName($priority->name);
            if ($normalized) {
                $this->priorityNameLookup[$normalized] = $priority->id;
            }
        }

        foreach (UserApiCustomerStage::where('user_id', $this->ownerId)->get(['id', 'stage_name']) as $stage) {
            $normalized = $this->normalizeName($stage->stage_name);
            if ($normalized) {
                $this->stageNameLookup[$normalized] = $stage->id;
            }
        }

        foreach (UserApiCustomerProcedure::where('user_id', $this->ownerId)->get(['id', 'procedure_name']) as $procedure) {
            $normalized = $this->normalizeName($procedure->procedure_name);
            if ($normalized) {
                $this->procedureNameLookup[$normalized] = $procedure->id;
            }
        }

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

        if ($this->tenantId) {
            foreach (User::where('tenant_id', $this->tenantId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->get(['id', 'first_name', 'last_name', 'email']) as $employee) {
                $fullName = trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
                $normalizedName = $this->normalizeName($fullName);
                $normalizedEmail = $this->normalizeName($employee->email);

                if ($normalizedName) {
                    $this->employeeNameLookup[$normalizedName] = $employee->id;
                }
                if ($normalizedEmail && $employee->email) {
                    $this->employeeEmailLookup[$normalizedEmail] = $employee->id;
                }
            }
        }
    }

    protected function resolveType(array $row): ?int
    {
        if (!empty($row['type_name'])) {
            $normalized = $this->normalizeName($row['type_name']);
            if ($normalized && isset($this->typeNameLookup[$normalized])) {
                return $this->typeNameLookup[$normalized];
            }
            $this->unresolvedTypes++;
        }

        return null;
    }

    protected function resolvePriority(array $row): ?int
    {
        if (!empty($row['priority_name'])) {
            $normalized = $this->normalizeName($row['priority_name']);
            if ($normalized && isset($this->priorityNameLookup[$normalized])) {
                return $this->priorityNameLookup[$normalized];
            }
            $this->unresolvedPriorities++;
        }

        return null;
    }

    protected function resolveStage(array $row): ?int
    {
        if (!empty($row['stage_name'])) {
            $normalized = $this->normalizeName($row['stage_name']);
            if ($normalized && isset($this->stageNameLookup[$normalized])) {
                return $this->stageNameLookup[$normalized];
            }
            $this->unresolvedStages++;
        }

        return null;
    }

    protected function resolveProcedure(array $row): ?int
    {
        if (!empty($row['procedure_name'])) {
            $normalized = $this->normalizeName($row['procedure_name']);
            if ($normalized && isset($this->procedureNameLookup[$normalized])) {
                return $this->procedureNameLookup[$normalized];
            }
            $this->unresolvedProcedures++;
        }

        return null;
    }

    protected function resolveEmployee(array $row): ?int
    {
        if (!empty($row['responsible_employee_email'])) {
            $normalized = $this->normalizeName($row['responsible_employee_email']);
            if ($normalized && isset($this->employeeEmailLookup[$normalized])) {
                return $this->employeeEmailLookup[$normalized];
            }
            $this->unresolvedEmployees++;

            return null;
        }

        if (!empty($row['responsible_employee_name'])) {
            $normalized = $this->normalizeName($row['responsible_employee_name']);
            if ($normalized && isset($this->employeeNameLookup[$normalized])) {
                return $this->employeeNameLookup[$normalized];
            }
            $this->unresolvedEmployees++;

            return null;
        }

        return null;
    }

    protected function flushLookupDropWarnings(): void
    {
        if ($this->unresolvedTypes > 0) {
            $this->recordWarning(null, "{$this->unresolvedTypes} customer types not found for this tenant and were skipped");
            $this->unresolvedTypes = 0;
        }
        if ($this->unresolvedPriorities > 0) {
            $this->recordWarning(null, "{$this->unresolvedPriorities} customer priorities not found for this tenant and were skipped");
            $this->unresolvedPriorities = 0;
        }
        if ($this->unresolvedStages > 0) {
            $this->recordWarning(null, "{$this->unresolvedStages} customer stages not found for this tenant and were skipped");
            $this->unresolvedStages = 0;
        }
        if ($this->unresolvedProcedures > 0) {
            $this->recordWarning(null, "{$this->unresolvedProcedures} customer procedures not found for this tenant and were skipped");
            $this->unresolvedProcedures = 0;
        }
        if ($this->unresolvedEmployees > 0) {
            $this->recordWarning(null, "{$this->unresolvedEmployees} employees not found for this tenant and were skipped");
            $this->unresolvedEmployees = 0;
        }
    }

    protected function resolveLocation(array $row, int $rowIndex): array
    {
        $cityId = null;
        $districtId = null;

        $cityName = $row['city_name_ar'] ?? $row['city_name_en'] ?? null;
        if ($cityName !== null && $cityName !== '') {
            $normalized = $this->normalizeName($cityName);
            if ($normalized && isset($this->cityNameLookup[$normalized])) {
                $cityId = $this->cityNameLookup[$normalized];
            } else {
                throw new \RuntimeException("Row {$rowIndex}: Unknown city name: '{$cityName}'.");
            }
        }

        $districtName = $row['district_name_ar'] ?? $row['district_name_en'] ?? null;
        if ($districtName !== null && $districtName !== '') {
            $normalized = $this->normalizeName($districtName);
            if (!$normalized || !isset($this->districtNameLookup[$normalized])) {
                throw new \RuntimeException("Row {$rowIndex}: Unknown district name: '{$districtName}'.");
            }

            $matches = $this->districtNameLookup[$normalized];
            if ($cityId !== null) {
                $matches = array_values(array_filter($matches, fn ($m) => (int) $m['city_id'] === $cityId));
            }

            if (count($matches) === 1) {
                $districtId = $matches[0]['id'];
            } elseif (count($matches) > 1) {
                throw new \RuntimeException(
                    "Row {$rowIndex}: Multiple districts named '{$districtName}' exist. Please specify city."
                );
            } else {
                throw new \RuntimeException(
                    "Row {$rowIndex}: District '{$districtName}' not found in the specified city."
                );
            }
        }

        return [$cityId, $districtId];
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
