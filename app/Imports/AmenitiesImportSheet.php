<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Amenity;
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
 * Imports the "Amenities" sheet into the target tenant, upserting by name so
 * property amenity references resolve on import. Runs BEFORE the properties sheet.
 */
class AmenitiesImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    protected ?int $defaultLanguageId;

    public function __construct(
        protected int $ownerId,
        protected IdRemapper $remap,
        protected bool $updateExisting = false,
        protected int $limit = 5000,
    ) {
        $this->defaultLanguageId = (int) Language::where('user_id', $ownerId)
            ->where('is_default', 1)
            ->value('id') ?: null;
    }

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

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $this->skipped++;

            return;
        }

        try {
            DB::transaction(function () use ($row, $name) {
                $model = Amenity::where('user_id', $this->ownerId)->where('name', $name)->first();
                if ($model && !$this->updateExisting) {
                    $this->skipped++;

                    return;
                }

                $exists = (bool) $model;
                if (!$model) {
                    $model = new Amenity();
                    $model->user_id = $this->ownerId;
                    $model->name = $name;
                }

                // language_id is NOT NULL — prefer the target's default language,
                // fall back to the exported value, then 1.
                $langId = $this->defaultLanguageId
                    ?? (is_numeric($row['language_id'] ?? null) ? (int) $row['language_id'] : null)
                    ?? ($model->language_id ?? 1);

                $slug = trim((string) ($row['slug'] ?? ''));
                $icon = trim((string) ($row['icon'] ?? ''));

                $model->language_id = $langId;
                $model->slug = $slug !== '' ? $slug : ($model->slug ?: make_slug($name));
                $model->icon = $icon !== '' ? $icon : ($model->icon ?? '');
                $model->status = ($row['status'] ?? '') !== '' ? (int) $row['status'] : ($model->status ?? 1);
                $model->serial_number = is_numeric($row['serial_number'] ?? null) ? (int) $row['serial_number'] : ($model->serial_number ?? 0);
                $model->save();

                $exists ? $this->updated++ : $this->imported++;
            });
        } catch (\Throwable $e) {
            $this->recordError($rowIndex, $e->getMessage());
            $this->skipped++;
        }
    }

    public function rules(): array
    {
        return [
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
}
