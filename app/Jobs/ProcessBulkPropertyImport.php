<?php

namespace App\Jobs;

use App\Models\Property\BulkImportBatch;
use App\Services\Property\BulkPropertyImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBulkPropertyImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $batchId,
        public readonly int $offset = 0,
        public readonly int $chunkSize = 500,
    ) {}

    public function handle(BulkPropertyImportService $service): void
    {
        $batch = BulkImportBatch::find($this->batchId);
        if (! $batch) {
            return;
        }

        $service->processChunk($batch, $this->offset, $this->chunkSize);
    }
}
