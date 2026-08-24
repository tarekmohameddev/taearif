<?php

namespace App\Domain\DataExport\Services;

use App\Domain\DataExport\Models\TenantDataImportBatch;
use App\Jobs\ProcessTenantDataImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class TenantDataImportQueueService
{
    public function storeUploadedFile(UploadedFile $file): string
    {
        $filename = Str::uuid()->toString() . '.xlsx';

        return $file->storeAs(
            config('tenant_data_import.storage_path'),
            $filename,
            'local'
        );
    }

    public function createBatch(
        int $ownerId,
        ?int $adminId,
        string $filePath,
        ?string $originalFilename,
        bool $updateExisting,
    ): TenantDataImportBatch {
        return TenantDataImportBatch::create([
            'owner_id' => $ownerId,
            'admin_id' => $adminId,
            'file_path' => $filePath,
            'original_filename' => $originalFilename,
            'update_existing' => $updateExisting,
            'status' => TenantDataImportBatch::STATUS_PENDING,
        ]);
    }

    public function dispatchImport(TenantDataImportBatch $batch): void
    {
        ProcessTenantDataImport::dispatch($batch->id);
    }
}
