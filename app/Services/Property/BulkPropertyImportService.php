<?php

namespace App\Services\Property;

use App\Jobs\ProcessBulkPropertyImport;
use App\Models\Property\BulkImportBatch;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BulkPropertyImportService
{
    public function createTableBatch(int $userId, array $units, ?int $projectId, ?int $buildingId, string $publishStatus): BulkImportBatch
    {
        $preview = [];
        foreach ($units as $index => $unit) {
            $preview[] = [
                'row' => $index + 1,
                'data' => $unit,
                'valid' => $this->validateUnitRow($unit),
                'errors' => $this->validateUnitRowErrors($unit),
            ];
        }

        $batch = BulkImportBatch::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'building_id' => $buildingId,
            'source' => 'table',
            'status' => 'pending',
            'publish_status' => $publishStatus,
            'total' => count($preview),
            'preview_data' => $preview,
        ]);

        return $batch;
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
                'valid' => $this->validateUnitRow($data),
                'errors' => $this->validateUnitRowErrors($data),
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
        $succeeded = $batch->succeeded;
        $failed = $batch->failed;

        $languageId = Language::where('user_id', $batch->user_id)->where('is_default', 1)->value('id');

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
                $propertyId = $this->createUnitFromRow($batch, $entry['data'] ?? [], $languageId);
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

    private function createUnitFromRow(BulkImportBatch $batch, array $data, ?int $languageId): int
    {
        return DB::transaction(function () use ($batch, $data, $languageId) {
            $property = Property::create([
                'user_id' => $batch->user_id,
                'created_by' => $batch->user_id,
                'project_id' => $batch->project_id ?? ($data['project_id'] ?? null),
                'building_id' => $batch->building_id ?? ($data['building_id'] ?? null),
                'price' => $data['price'] ?? null,
                'area' => $data['area'] ?? null,
                'beds' => $data['beds'] ?? null,
                'bath' => $data['bath'] ?? null,
                'purpose' => $data['purpose'] ?? 'sale',
                'listing_purpose' => $data['listing_purpose'] ?? ($data['purpose'] ?? 'sale'),
                'unit_status' => $data['unit_status'] ?? 'available',
                'publish_status' => $batch->publish_status,
                'property_type' => $data['property_type'] ?? 'apartment',
                'status' => $batch->publish_status === 'published' ? 1 : 0,
                'featured_image' => $data['featured_image'] ?? '',
            ]);

            if ($languageId && ! empty($data['title'])) {
                PropertyContent::storePropertyContent($batch->user_id, $property->id, [
                    'language_id' => $languageId,
                    'category_id' => $data['category_id'] ?? 1,
                    'title' => $data['title'],
                    'slug' => str_replace('.', '', Str::slug($data['title'])),
                    'address' => $data['address'] ?? '',
                    'description' => $data['description'] ?? $data['title'],
                ]);
            }

            return $property->id;
        });
    }

    private function validateUnitRow(array $unit): bool
    {
        return empty($this->validateUnitRowErrors($unit));
    }

    private function validateUnitRowErrors(array $unit): array
    {
        $errors = [];
        if (empty($unit['title'])) {
            $errors[] = 'title is required';
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
