<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

/**
 * Imports the "CRM Settings" sheet (customer types / priorities / stages /
 * procedures) into the target tenant. Upserts by name (per kind), so customer
 * type_id/priority_id/stage_id/procedure_id references resolve on import.
 * Runs BEFORE the customers sheet.
 */
class CrmSettingsImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    public function __construct(
        protected int $ownerId,
        protected IdRemapper $remap,
        protected bool $updateExisting = false,
        protected int $limit = 5000,
    ) {}

    public function limit(): int
    {
        return $this->limit;
    }

    public function onRow(Row $row): void
    {
        $rowIndex = $row->getIndex();
        $row = $row->toArray();

        if (!empty($row['_skip_empty_row'])) {
            return;
        }

        $kind = strtolower(trim((string) ($row['kind'] ?? '')));
        $name = trim((string) ($row['name'] ?? ''));

        if ($kind === '' || $name === '') {
            $this->skipped++;

            return;
        }

        try {
            DB::transaction(function () use ($row, $kind, $name) {
                switch ($kind) {
                    case 'type':
                        $this->upsertType($row, $name);
                        break;
                    case 'priority':
                        $this->upsertPriority($row, $name);
                        break;
                    case 'stage':
                        $this->upsertStage($row, $name);
                        break;
                    case 'procedure':
                        $this->upsertProcedure($row, $name);
                        break;
                    default:
                        $this->skipped++;
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
            'kind' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:255',
        ];
    }

    public function prepareForValidation($data, $index): array
    {
        $data = is_array($data) ? $data : (array) $data;

        foreach ($data as $key => $value) {
            if (is_int($value) || is_float($value)) {
                $data[$key] = (string) $value;
            }
        }

        $hasData = !empty(array_filter($data, fn ($v) => !is_null($v) && $v !== ''));
        if (!$hasData) {
            $data['_skip_empty_row'] = true;
        }

        return $data;
    }

    private function upsertType(array $row, string $name): void
    {
        $model = UserApiCustomerType::where('user_id', $this->ownerId)->where('name', $name)->first();
        if ($model && !$this->updateExisting) {
            $this->skipped++;

            return;
        }

        $exists = (bool) $model;
        if (!$model) {
            $model = new UserApiCustomerType();
            $model->user_id = $this->ownerId;
            $model->name = $name;
        }

        $value = trim((string) ($row['value'] ?? ''));
        $model->value = $value !== '' ? substr($value, 0, 50) : ($model->value ?: substr($name, 0, 50));
        $model->order = $this->intOr($row['order'] ?? null, $model->order ?? 1);
        $model->color = $this->strOr($row['color'] ?? null, $model->color);
        $model->icon = $this->strOr($row['icon'] ?? null, $model->icon);
        $model->is_active = $this->boolOr($row['is_active'] ?? null, $model->is_active ?? 1);
        $model->save();

        $exists ? $this->updated++ : $this->imported++;
    }

    private function upsertPriority(array $row, string $name): void
    {
        $model = UserApiCustomerPriority::where('user_id', $this->ownerId)->where('name', $name)->first();
        if ($model && !$this->updateExisting) {
            $this->skipped++;

            return;
        }

        $exists = (bool) $model;
        if (!$model) {
            $model = new UserApiCustomerPriority();
            $model->user_id = $this->ownerId;
            $model->name = $name;
        }

        $model->value = $this->intOr($row['value'] ?? null, $model->value ?? 0);
        $model->order = $this->intOr($row['order'] ?? null, $model->order ?? 0);
        $model->color = $this->strOr($row['color'] ?? null, $model->color);
        $model->icon = $this->strOr($row['icon'] ?? null, $model->icon);
        $model->is_active = $this->boolOr($row['is_active'] ?? null, $model->is_active ?? 1);
        $model->save();

        $exists ? $this->updated++ : $this->imported++;
    }

    private function upsertStage(array $row, string $name): void
    {
        $model = UserApiCustomerStage::where('user_id', $this->ownerId)->where('stage_name', $name)->first();
        if ($model && !$this->updateExisting) {
            $this->skipped++;

            return;
        }

        $exists = (bool) $model;
        if (!$model) {
            $model = new UserApiCustomerStage();
            $model->user_id = $this->ownerId;
            $model->stage_name = $name;
        }

        $model->order = $this->intOr($row['order'] ?? null, $model->order ?? 0);
        $model->color = $this->strOr($row['color'] ?? null, $model->color);
        $model->icon = $this->strOr($row['icon'] ?? null, $model->icon);
        $model->description = $this->strOr($row['description'] ?? null, $model->description);
        $model->is_active = $this->boolOr($row['is_active'] ?? null, $model->is_active ?? 1);
        $model->save();

        $exists ? $this->updated++ : $this->imported++;
    }

    private function upsertProcedure(array $row, string $name): void
    {
        $model = UserApiCustomerProcedure::where('user_id', $this->ownerId)->where('procedure_name', $name)->first();
        if ($model && !$this->updateExisting) {
            $this->skipped++;

            return;
        }

        $exists = (bool) $model;
        if (!$model) {
            $model = new UserApiCustomerProcedure();
            $model->user_id = $this->ownerId;
            $model->procedure_name = $name;
        }

        $model->order = $this->intOr($row['order'] ?? null, $model->order ?? 0);
        $model->color = $this->strOr($row['color'] ?? null, $model->color);
        $model->icon = $this->strOr($row['icon'] ?? null, $model->icon);
        $model->description = $this->strOr($row['description'] ?? null, $model->description);
        $model->is_active = $this->boolOr($row['is_active'] ?? null, $model->is_active ?? 1);
        $model->save();

        $exists ? $this->updated++ : $this->imported++;
    }

    private function intOr(mixed $value, ?int $fallback): int
    {
        return ($value !== null && $value !== '' && is_numeric($value)) ? (int) $value : (int) ($fallback ?? 0);
    }

    private function strOr(mixed $value, ?string $fallback): ?string
    {
        return ($value !== null && $value !== '') ? (string) $value : $fallback;
    }

    private function boolOr(mixed $value, mixed $fallback): int
    {
        if ($value === null || $value === '') {
            return (int) (bool) $fallback;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'نعم'], true) ? 1 : (int) (bool) $value;
    }
}
