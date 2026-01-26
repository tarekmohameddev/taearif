<?php

namespace App\Jobs;

use App\Services\Analytics\PageviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldPageviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of months to keep records
     *
     * @var int
     */
    public int $monthsToKeep;

    /**
     * Whether to aggregate before deleting
     *
     * @var bool
     */
    public bool $aggregateBeforeDelete;

    /**
     * Create a new job instance.
     *
     * @param int $monthsToKeep
     * @param bool $aggregateBeforeDelete
     */
    public function __construct(int $monthsToKeep = 12, bool $aggregateBeforeDelete = false)
    {
        $this->monthsToKeep = $monthsToKeep;
        $this->aggregateBeforeDelete = $aggregateBeforeDelete;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PageviewService $pageviewService)
    {
        try {
            Log::info('Starting cleanup of old pageview records', [
                'months_to_keep' => $this->monthsToKeep,
                'aggregate_before_delete' => $this->aggregateBeforeDelete,
            ]);

            $deleted = $pageviewService->cleanupOldRecords(
                $this->monthsToKeep,
                $this->aggregateBeforeDelete
            );

            Log::info('Completed cleanup of old pageview records', [
                'records_deleted' => $deleted,
                'months_to_keep' => $this->monthsToKeep,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old pageview records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
