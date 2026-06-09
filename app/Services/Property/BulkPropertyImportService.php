<?php

namespace App\Services\Property;

use App\Jobs\ProcessBulkPropertyImport;
use App\Models\Building;
use App\Models\Membership;
use App\Models\Property\BulkImportBatch;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\Project;
use App\Rules\PropertyTypeRule;
use App\Rules\ValidListingPurposeUnitStatusCombination;
use App\Services\MembershipCacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BulkPropertyImportService
{
    public function createTableBatch(
        int $ownerId,
        array $units,
        ?int $projectId,
        ?int $buildingId,
        string $publishStatus,
        ?int $actorId = null,
    ): BulkImportBatch {
        $preview = $this->buildTablePreview($ownerId, $units, $projectId, $buildingId, $publishStatus);

        $batch = BulkImportBatch::create([
            'user_id' => $ownerId,
            'project_id' => $projectId,
            'building_id' => $buildingId,
            'source' => 'table',
            'status' => 'pending',
            'publish_status' => $publishStatus,
            'total' => count($preview),
            'preview_data' => $preview,
            'report' => [
                'meta' => [
                    'created_by' => $actorId ?? $ownerId,
                ],
            ],
        ]);

        return $batch;
    }

    /**
     * @return list<array{row: int, data: array<string, mixed>, valid: bool, errors: list<string>}>
     */
    public function buildTablePreview(
        int $tenantOwnerId,
        array $units,
        ?int $projectId,
        ?int $buildingId,
        string $publishStatus,
    ): array {
        $preview = [];

        foreach ($units as $index => $unit) {
            $preview[] = [
                'row' => $index + 1,
                'data' => $unit,
                'valid' => $this->validateUnitRow($unit, $tenantOwnerId, $projectId, $buildingId, $publishStatus),
                'errors' => $this->validateUnitRowErrors($unit, $tenantOwnerId, $projectId, $buildingId, $publishStatus),
            ];
        }

        return $preview;
    }

    /**
     * @return array{status: string, total: int, valid: int, invalid: int, rows: list<array{row: int|null, valid: bool, errors: list<string>}>}
     */
    public function buildInitialReport(BulkImportBatch $batch): array
    {
        $rows = [];
        $valid = 0;
        $invalid = 0;

        foreach ($batch->preview_data ?? [] as $entry) {
            $isValid = (bool) ($entry['valid'] ?? false);
            if ($isValid) {
                $valid++;
            } else {
                $invalid++;
            }

            $rows[] = [
                'row' => $entry['row'] ?? null,
                'valid' => $isValid,
                'errors' => $entry['errors'] ?? [],
            ];
        }

        return [
            'status' => $batch->status,
            'total' => (int) $batch->total,
            'valid' => $valid,
            'invalid' => $invalid,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{status: string, total: int, valid: int, invalid: int, rows: list<array{row: int|null, valid: bool, errors: list<string>}>}
     */
    public function buildInitialReportFromPreview(array $preview, string $status = 'pending'): array
    {
        $rows = [];
        $valid = 0;
        $invalid = 0;

        foreach ($preview as $entry) {
            $isValid = (bool) ($entry['valid'] ?? false);
            if ($isValid) {
                $valid++;
            } else {
                $invalid++;
            }

            $rows[] = [
                'row' => $entry['row'] ?? null,
                'valid' => $isValid,
                'errors' => $entry['errors'] ?? [],
            ];
        }

        return [
            'status' => $status,
            'total' => count($preview),
            'valid' => $valid,
            'invalid' => $invalid,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{status: string, message: string, limit?: int, used?: int}|null
     */
    public function membershipLimitError(int $ownerId, int $unitsToAdd): ?array
    {
        $membership = MembershipCacheService::getActiveMembership($ownerId);

        if (! ($membership instanceof Membership) || ! $membership->package) {
            return [
                'status' => 'fail',
                'message' => 'No active package found for the user.',
            ];
        }

        $realEstateLimit = $membership->package->real_estate_limit_number;

        if (is_null($realEstateLimit)) {
            return null;
        }

        $currentPropertyCount = Property::where('user_id', $ownerId)
            ->where('completion_status', 'complete')
            ->count();

        if ($currentPropertyCount + $unitsToAdd > $realEstateLimit) {
            return [
                'status' => false,
                'message' => 'You have reached your property listing limit.',
                'limit' => $realEstateLimit,
                'used' => $currentPropertyCount,
            ];
        }

        return null;
    }

    public function createExcelPreviewBatch(int $userId, UploadedFile $file, ?int $projectId, ?int $buildingId, string $publishStatus): BulkImportBatch
    {
        $rows = Excel::toArray([], $file)[0] ?? [];
        $headers = array_shift($rows) ?? [];
        $preview = [];

        foreach ($rows as $index => $row) {
            if ($this->isEmptyRow($row)) {
                continue;
            }
            $data = $this->mapExcelRow($headers, $row);
            $preview[] = [
                'row' => $index + 2,
                'data' => $data,
                'valid' => $this->validateUnitRow($data, $userId, $projectId, $buildingId, $publishStatus),
                'errors' => $this->validateUnitRowErrors($data, $userId, $projectId, $buildingId, $publishStatus),
            ];
        }

        return BulkImportBatch::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'building_id' => $buildingId,
            'source' => 'excel',
            'status' => 'pending',
            'publish_status' => $publishStatus,
            'total' => count($preview),
            'preview_data' => $preview,
        ]);
    }

    public function applyBatch(BulkImportBatch $batch): void
    {
        $batch->update(['status' => 'processing']);
        ProcessBulkPropertyImport::dispatch($batch->id, 0, 500);
    }

    public function processChunk(BulkImportBatch $batch, int $offset, int $chunkSize): void
    {
        $rows = collect($batch->preview_data ?? [])->slice($offset, $chunkSize)->values();
        $report = $batch->report ?? ['rows' => []];
        if (! isset($report['rows'])) {
            $report['rows'] = [];
        }
        $succeeded = $batch->succeeded;
        $failed = $batch->failed;

        $languageId = Language::where('user_id', $batch->user_id)->where('is_default', 1)->value('id');
        $actorId = $report['meta']['created_by'] ?? $batch->user_id;

        foreach ($rows as $entry) {
            if (! ($entry['valid'] ?? false)) {
                $failed++;
                $report['rows'][] = [
                    'row' => $entry['row'] ?? null,
                    'status' => 'failed',
                    'errors' => $entry['errors'] ?? ['Invalid row'],
                ];
                continue;
            }

            try {
                $propertyId = $this->createUnitFromRow($batch, $entry['data'] ?? [], $languageId, $actorId);
                $succeeded++;
                $report['rows'][] = [
                    'row' => $entry['row'] ?? null,
                    'status' => 'succeeded',
                    'property_id' => $propertyId,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $report['rows'][] = [
                    'row' => $entry['row'] ?? null,
                    'status' => 'failed',
                    'errors' => [$e->getMessage()],
                ];
            }
        }

        $processed = $offset + $rows->count();
        $total = count($batch->preview_data ?? []);
        $status = $processed >= $total ? 'done' : 'processing';

        $batch->update([
            'succeeded' => $succeeded,
            'failed' => $failed,
            'report' => $report,
            'status' => $status,
        ]);

        if ($status === 'processing') {
            ProcessBulkPropertyImport::dispatch($batch->id, $processed, $chunkSize);
        }
    }

    private function createUnitFromRow(BulkImportBatch $batch, array $data, ?int $languageId, ?int $actorId = null): int
    {
        return DB::transaction(function () use ($batch, $data, $languageId, $actorId) {
            $rowPublish = $data['publish_status'] ?? $batch->publish_status;

            $property = Property::create([
                'user_id' => $batch->user_id,
                'created_by' => $actorId ?? $batch->user_id,
                'project_id' => $data['project_id'] ?? $batch->project_id ?? null,
                'building_id' => $data['building_id'] ?? $batch->building_id ?? null,
                'price' => $data['price'] ?? null,
                'area' => $data['area'] ?? null,
                'beds' => $data['beds'] ?? null,
                'bath' => $data['bath'] ?? null,
                'purpose' => $data['purpose'] ?? 'sale',
                'listing_purpose' => $data['listing_purpose'] ?? ($data['purpose'] ?? 'sale'),
                'unit_status' => $data['unit_status'] ?? 'available',
                'publish_status' => $rowPublish,
                'property_type' => $data['property_type'] ?? 'apartment',
                'status' => $rowPublish === 'published' ? 1 : 0,
                'completion_status' => 'complete',
                'import_batch_id' => (string) $batch->id,
                'featured_image' => $data['featured_image'] ?? '',
            ]);

            if ($languageId && ! empty($data['title'])) {
                $description = $data['description'] ?? $data['title'];
                PropertyContent::storePropertyContent($batch->user_id, $property->id, [
                    'language_id' => $languageId,
                    'category_id' => $data['category_id'] ?? 1,
                    'country_id' => $data['country_id'] ?? null,
                    'state_id' => $data['state_id'] ?? null,
                    'city_id' => $data['city_id'] ?? null,
                    'title' => $data['title'],
                    'slug' => str_replace('.', '', Str::slug($data['title'])),
                    'address' => $data['address'] ?? '',
                    'description' => $description,
                    'meta_keyword' => $data['meta_keyword'] ?? null,
                    'meta_description' => $data['meta_description'] ?? Str::limit((string) $description, 150),
                ]);
            }

            return $property->id;
        });
    }

    private function validateUnitRow(
        array $unit,
        int $tenantOwnerId,
        ?int $batchProjectId = null,
        ?int $batchBuildingId = null,
        string $batchPublishStatus = 'draft',
    ): bool {
        return empty($this->validateUnitRowErrors($unit, $tenantOwnerId, $batchProjectId, $batchBuildingId, $batchPublishStatus));
    }

    /**
     * @return list<string>
     */
    private function validateUnitRowErrors(
        array $unit,
        int $tenantOwnerId,
        ?int $batchProjectId = null,
        ?int $batchBuildingId = null,
        string $batchPublishStatus = 'draft',
    ): array {
        $errors = [];

        if (empty($unit['title'])) {
            $errors[] = 'title is required';
        }

        if (isset($unit['price']) && $unit['price'] !== null && $unit['price'] !== '' && (! is_numeric($unit['price']) || (float) $unit['price'] < 0)) {
            $errors[] = 'price must be a non-negative number';
        }

        if (isset($unit['area']) && $unit['area'] !== null && $unit['area'] !== '' && (! is_numeric($unit['area']) || (float) $unit['area'] < 0)) {
            $errors[] = 'area must be a non-negative number';
        }

        if (isset($unit['beds']) && $unit['beds'] !== null && $unit['beds'] !== '' && (! is_numeric($unit['beds']) || (int) $unit['beds'] < 0)) {
            $errors[] = 'beds must be a non-negative integer';
        }

        if (isset($unit['bath']) && $unit['bath'] !== null && $unit['bath'] !== '' && (! is_numeric($unit['bath']) || (int) $unit['bath'] < 0)) {
            $errors[] = 'bath must be a non-negative integer';
        }

        if (isset($unit['listing_purpose']) && $unit['listing_purpose'] !== null && $unit['listing_purpose'] !== '' && ! in_array($unit['listing_purpose'], ['sale', 'rent'], true)) {
            $errors[] = 'listing_purpose must be sale or rent';
        }

        if (isset($unit['unit_status']) && $unit['unit_status'] !== null && $unit['unit_status'] !== '' && ! in_array($unit['unit_status'], ['available', 'reserved', 'sold', 'rented'], true)) {
            $errors[] = 'unit_status is invalid';
        }

        $comboRule = new ValidListingPurposeUnitStatusCombination($unit);
        if (! $comboRule->passes('unit_status', $unit['unit_status'] ?? 'available')) {
            $errors[] = $comboRule->message();
        }

        $rowPublish = $unit['publish_status'] ?? $batchPublishStatus;
        if (! in_array($rowPublish, ['draft', 'published'], true)) {
            $errors[] = 'publish_status must be draft or published';
        }

        if (isset($unit['property_type']) && $unit['property_type'] !== null && $unit['property_type'] !== '') {
            $typeRule = new PropertyTypeRule();
            if (! $typeRule->passes('property_type', $unit['property_type'])) {
                $errors[] = $typeRule->message();
            }
        }

        $projectId = $unit['project_id'] ?? $batchProjectId;
        if ($projectId !== null && $projectId !== '') {
            $exists = Project::where('id', $projectId)->where('user_id', $tenantOwnerId)->exists();
            if (! $exists) {
                $errors[] = 'project_id does not belong to tenant';
            }
        }

        $buildingId = $unit['building_id'] ?? $batchBuildingId;
        if ($buildingId !== null && $buildingId !== '') {
            $exists = Building::where('id', $buildingId)->where('user_id', $tenantOwnerId)->exists();
            if (! $exists) {
                $errors[] = 'building_id does not belong to tenant';
            }
        }

        return $errors;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->filter(fn ($v) => $v !== null && $v !== '')->isEmpty();
    }

    private function mapExcelRow(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $key = Str::snake(trim((string) $header));
            if ($key !== '') {
                $data[$key] = $row[$i] ?? null;
            }
        }

        return $data;
    }
}
