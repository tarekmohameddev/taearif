<?php

namespace App\Jobs;

use App\Models\Property\BulkImportBatch;
use App\Services\Property\BulkPropertyImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessBulkPropertyImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    public int $timeout;

    public int $backoff;

    public readonly int $chunkSize;

    public function __construct(
        public readonly int $batchId,
        public readonly int $offset = 0,
        ?int $chunkSize = null,
    ) {
        $this->tries = max(1, (int) config('bulk_import.job.tries', 3));
        $this->timeout = max(1, (int) config('bulk_import.job.timeout', 120));
        $this->backoff = max(1, (int) config('bulk_import.job.backoff', 30));
        $this->chunkSize = max(1, $chunkSize ?? (int) config('bulk_import.chunk_size', 50));

        $queue = config('bulk_import.job.queue');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(BulkPropertyImportService $service): void
    {
        $batch = BulkImportBatch::find($this->batchId);
        if (! $batch || in_array($batch->status, ['done', 'failed'], true)) {
            return;
        }

        $service->processChunk($batch, $this->offset, $this->chunkSize);
    }

    public function failed(Throwable $e): void
    {
        $batch = BulkImportBatch::find($this->batchId);
        if (! $batch || $batch->status === 'done') {
            return;
        }

        app(BulkPropertyImportService::class)->markBatchFailed(
            $batch,
            $e->getMessage() ?: 'Bulk import job failed.',
        );
    }
}
