<?php

namespace App\Jobs;

use App\Models\AdvertisingImport;
use App\Services\Advertising\AdvertisingImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeAdvertisingImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $importId) {}

    public function handle(AdvertisingImportService $service): void
    {
        $import = AdvertisingImport::find($this->importId);
        if (! $import) {
            return;
        }

        $import->update(['status' => 'fetching']);

        try {
            $rawData = [
                'title' => 'Imported listing',
                'price' => null,
                'description' => '',
                'images' => [],
                'source_url' => $import->source_url,
                'platform' => $import->platform,
            ];

            $import->update([
                'status' => 'review',
                'raw_data' => $rawData,
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
