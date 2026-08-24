<?php

namespace App\Imports;

use App\Imports\Support\IdRemapper;
use App\Imports\Support\ImportSummary;
use App\Imports\Support\MissingDefaultLanguageException;
use App\Models\User\Language;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\ProjectGalleryImg;
use App\Models\User\RealestateManagement\ProjectFloorplanImg;
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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ProjectsDataImportSheet implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsOnError, WithLimit
{
    use SkipsFailures, SkipsErrors, ImportSummary;

    protected int $defaultLanguageId;

    /** @var array<string, list<array{id: int, city_id: int|null}>> */
    protected array $districtNameLookup = [];

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
            throw new MissingDefaultLanguageException('projects');
        }

        $this->loadDistrictLookups();
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

        try {
            DB::transaction(function () use ($row, $rowIndex) {
                $cityId = $this->resolveCityId($row);
                $stateId = $this->resolveStateId($row, $rowIndex, $cityId);

                $amenities = array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) ($row['amenities'] ?? ''))
                )));

                $sourceId = (int) ($row['id'] ?? 0);
                $existing = $sourceId
                    ? Project::where('user_id', $this->ownerId)->where('id', $sourceId)->first()
                    : null;

                if ($existing && !$this->updateExisting) {
                    $this->remap->put('project', $sourceId, $existing->id);
                    $this->skipped++;

                    return;
                }

                $attributes = [
                    'developer' => $row['developer'] ?? null,
                    'min_price' => $row['min_price'] ?? null,
                    'max_price' => $row['max_price'] ?? null,
                    'units' => $row['units'] ?? null,
                    'completion_date' => $this->normalizeDateValue($row['completion_date'] ?? null),
                    'complete_status' => ($row['complete_status'] === null || $row['complete_status'] === '') ? 0 : $row['complete_status'],
                    'published' => $this->toBool($row['published'] ?? null),
                    'featured' => $this->toBool($row['featured'] ?? null),
                    'city_id' => $cityId,
                    'state_id' => $stateId,
                    'latitude' => $row['latitude'] ?? null,
                    'longitude' => $row['longitude'] ?? null,
                    'featured_image' => $this->relPath($row['featured_image_url'] ?? null),
                    'brochure' => $this->relPath($row['brochure_url'] ?? null),
                    'video_url' => $row['video_url'] ?? null,
                    'amenities' => $amenities,
                ];

                if ($existing) {
                    $existing->update($attributes);
                    $project = $existing;
                    // Refresh children so an update doesn't duplicate them.
                    ProjectContent::where('project_id', $project->id)->delete();
                    ProjectGalleryImg::where('project_id', $project->id)->delete();
                    ProjectFloorplanImg::where('project_id', $project->id)->delete();
                    $this->updated++;
                } else {
                    $project = Project::create($attributes + [
                        'user_id' => $this->ownerId,
                        'created_by' => auth()->id() ?? $this->ownerId,
                    ]);
                    $this->imported++;
                }

                $title = $row['title'] ?? '';
                ProjectContent::create([
                    'user_id' => $this->ownerId,
                    'project_id' => $project->id,
                    'language_id' => $this->defaultLanguageId,
                    'title' => $title,
                    'slug' => !empty($row['slug']) ? $row['slug'] : make_slug($title),
                    'address' => $row['address'] ?? null,
                    'description' => $row['description'] ?? null,
                ]);

                $galleryPaths = $this->splitImageUrls($row['gallery_image_urls'] ?? null);
                if ($galleryPaths !== []) {
                    ProjectGalleryImg::insertManyForProject($this->ownerId, $project->id, $galleryPaths);
                }

                $floorplanPaths = $this->splitImageUrls($row['floorplan_image_urls'] ?? null);
                if ($floorplanPaths !== []) {
                    ProjectFloorplanImg::insertManyForProject($this->ownerId, $project->id, $floorplanPaths);
                }

                $this->remap->put('project', $sourceId, $project->id);
            });
        } catch (\Throwable $e) {
            $this->recordError($rowIndex, $e->getMessage());
            $this->skipped++;
        }
    }

    public function rules(): array
    {
        return [
            // Align with TenantDataImportService required_headers: title.
            // Maatwebsite validates as [rowIndex => row]; use *._skip_empty_row so exclude_if
            // resolves against the same nested row (plain _skip_empty_row never matches).
            'title' => 'exclude_if:*._skip_empty_row,1|required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'developer' => 'nullable|string|max:255',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'city_id' => 'nullable|integer',
            'district_name_ar' => 'nullable|string|max:255',
        ];
    }

    public function prepareForValidation($data, $index): array
    {
        $data = is_array($data) ? $data : (array) $data;

        // Convert Excel date serials before stringifying numerics.
        if (array_key_exists('completion_date', $data)) {
            $data['completion_date'] = $this->normalizeDateValue($data['completion_date']);
        }

        // Excel returns numeric-looking cells (phone numbers, ids) as int/float;
        // stringify them so `string`/`max` validation rules don't wrongly reject the row.
        foreach ($data as $key => $value) {
            if ($key === 'completion_date') {
                continue;
            }
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

    /**
     * Normalize a date-like cell to Y-m-d. Excel serial numbers are converted
     * via PhpSpreadsheet so completion_date round-trips to the same calendar day.
     */
    protected function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $trimmed, $m)) {
                return $m[1];
            }
            if (!is_numeric($trimmed)) {
                return $trimmed;
            }
            $value = (float) $trimmed;
        }

        if (is_int($value) || is_float($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        return null;
    }

    protected function loadDistrictLookups(): void
    {
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
    }

    protected function resolveCityId(array $row): ?int
    {
        $cityIdRaw = $row['city_id'] ?? null;
        if ($cityIdRaw === null || $cityIdRaw === '') {
            return null;
        }

        if (!is_numeric($cityIdRaw)) {
            return null;
        }

        $cityId = (int) $cityIdRaw;

        return UserCity::where('id', $cityId)->exists() ? $cityId : null;
    }

    protected function resolveStateId(array $row, int $rowIndex, ?int $cityId): ?int
    {
        $districtName = $row['district_name_ar'] ?? null;
        if ($districtName === null || $districtName === '') {
            return null;
        }

        $normalized = $this->normalizeName($districtName);
        if (!$normalized || !isset($this->districtNameLookup[$normalized])) {
            throw new \RuntimeException("Row {$rowIndex}: Unknown district_name_ar: '{$districtName}'.");
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
                "Row {$rowIndex}: Multiple districts named '{$districtName}' exist. Please specify city_id."
            );
        }

        throw new \RuntimeException(
            "Row {$rowIndex}: District '{$districtName}' not found in the specified city."
        );
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
