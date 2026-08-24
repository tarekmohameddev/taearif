<?php

namespace App\Jobs;

use App\Domain\DataExport\Models\TenantDataImportBatch;
use App\Domain\DataExport\Services\DataExportImportLogger;
use App\Domain\DataExport\Services\TenantDataImportService;
use App\Notifications\TenantDataImportProcessed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessTenantDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(
        public readonly int $batchId,
    ) {
        $this->tries = max(1, (int) config('tenant_data_import.job.tries', 1));
        $this->timeout = max(1, (int) config('tenant_data_import.job.timeout', 1200));

        $queue = config('tenant_data_import.job.queue');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(
        TenantDataImportService $importService,
        DataExportImportLogger $logger,
    ): void {
        $batch = TenantDataImportBatch::find($this->batchId);
        if (! $batch || in_array($batch->status, [TenantDataImportBatch::STATUS_DONE, TenantDataImportBatch::STATUS_FAILED], true)) {
            return;
        }

        $batch->update(['status' => TenantDataImportBatch::STATUS_PROCESSING]);

        try {
            $uploadedFile = $this->makeUploadedFile($batch);
            $result = $importService->import(
                (int) $batch->owner_id,
                $uploadedFile,
                (bool) $batch->update_existing,
            );

            $batch->update([
                'status' => TenantDataImportBatch::STATUS_DONE,
                'result' => $result,
                'error' => null,
            ]);

            $logger->recordImport(
                (int) $batch->owner_id,
                $result,
                (bool) $batch->update_existing,
                $batch->admin_id,
            );

            $this->notifyAdmin($batch);
        } catch (Throwable $e) {
            $this->markBatchFailed($batch, $e, $logger);
        } finally {
            $this->deleteTempFile($batch->file_path);
        }
    }

    public function failed(Throwable $e): void
    {
        $batch = TenantDataImportBatch::find($this->batchId);
        if (! $batch || $batch->status === TenantDataImportBatch::STATUS_DONE) {
            return;
        }

        if ($batch->status !== TenantDataImportBatch::STATUS_FAILED) {
            $this->markBatchFailed($batch, $e, app(DataExportImportLogger::class));
        }

        $this->deleteTempFile($batch->file_path);
    }

    private function makeUploadedFile(TenantDataImportBatch $batch): UploadedFile
    {
        $absolutePath = storage_path('app/' . $batch->file_path);

        return new UploadedFile(
            $absolutePath,
            $batch->original_filename ?? basename($batch->file_path),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function markBatchFailed(
        TenantDataImportBatch $batch,
        Throwable $e,
        DataExportImportLogger $logger,
    ): void {
        $message = $e->getMessage() ?: 'Tenant data import job failed.';

        $batch->update([
            'status' => TenantDataImportBatch::STATUS_FAILED,
            'error' => $message,
        ]);

        $logger->recordImportFailure(
            (int) $batch->owner_id,
            $message,
            (bool) $batch->update_existing,
            $batch->admin_id,
        );

        $this->notifyAdmin($batch);
    }

    private function notifyAdmin(TenantDataImportBatch $batch): void
    {
        try {
            $fresh = $batch->fresh(['admin']);
            if (! $fresh || ! $fresh->admin_id || ! $fresh->admin) {
                return;
            }

            $fresh->admin->notify(new TenantDataImportProcessed($fresh->fresh()));
        } catch (Throwable $e) {
            Log::warning('Failed to send tenant data import notification', [
                'batch_id' => $batch->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function deleteTempFile(?string $filePath): void
    {
        if ($filePath === null || $filePath === '') {
            return;
        }

        Storage::disk('local')->delete($filePath);
    }
}
