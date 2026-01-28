<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupOldAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of months to keep records
     *
     * @var int
     */
    public int $monthsToKeep;

    /**
     * Create a new job instance.
     *
     * @param int $monthsToKeep
     */
    public function __construct(int $monthsToKeep = 12)
    {
        $this->monthsToKeep = $monthsToKeep;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cutoffDate = Carbon::today()->subMonths($this->monthsToKeep)->toDateString();

        Log::info('Starting cleanup of old analytics records', [
            'months_to_keep' => $this->monthsToKeep,
            'cutoff_date' => $cutoffDate,
        ]);

        try {
            // Clean up pageview_analytics table
            $pageviewDeleted = $this->cleanupPageviewAnalytics($cutoffDate);

            // Clean up analytics_daily_summary table
            $summaryDeleted = $this->cleanupDailySummary($cutoffDate);

            Log::info('Completed cleanup of old analytics records', [
                'cutoff_date' => $cutoffDate,
                'pageview_records_deleted' => $pageviewDeleted,
                'summary_records_deleted' => $summaryDeleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old analytics records', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean up old pageview_analytics records
     *
     * @param string $cutoffDate
     * @return int
     */
    protected function cleanupPageviewAnalytics(string $cutoffDate): int
    {
        $deleted = 0;
        $chunkSize = 1000;

        do {
            $deletedChunk = DB::table('pageview_analytics')
                ->where('date_bucket', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $deleted += $deletedChunk;

            // Small delay to avoid overwhelming the database
            if ($deletedChunk > 0) {
                usleep(100000); // 0.1 seconds
            }
        } while ($deletedChunk > 0);

        return $deleted;
    }

    /**
     * Clean up old analytics_daily_summary records
     *
     * @param string $cutoffDate
     * @return int
     */
    protected function cleanupDailySummary(string $cutoffDate): int
    {
        $deleted = 0;
        $chunkSize = 1000;

        do {
            $deletedChunk = DB::table('analytics_daily_summary')
                ->where('date', '<', $cutoffDate)
                ->limit($chunkSize)
                ->delete();

            $deleted += $deletedChunk;

            // Small delay to avoid overwhelming the database
            if ($deletedChunk > 0) {
                usleep(100000); // 0.1 seconds
            }
        } while ($deletedChunk > 0);

        return $deleted;
    }
}
