<?php

namespace App\Console\Commands;

use App\Services\Analytics\PageviewService;
use Illuminate\Console\Command;

class CleanupOldPageviewsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:cleanup-old-pageviews
                            {--months=12 : Number of months to keep records}
                            {--aggregate : Aggregate records into monthly summaries before deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old pageview analytics records older than specified months';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(PageviewService $pageviewService)
    {
        $monthsToKeep = (int) $this->option('months');
        $aggregateBeforeDelete = $this->option('aggregate');

        if ($monthsToKeep < 1) {
            $this->error('Months must be at least 1');
            return Command::FAILURE;
        }

        $this->info("Cleaning up pageview records older than {$monthsToKeep} months...");

        try {
            $deleted = $pageviewService->cleanupOldRecords($monthsToKeep, $aggregateBeforeDelete);

            $this->info("Successfully deleted {$deleted} old pageview records.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to cleanup old pageviews: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
