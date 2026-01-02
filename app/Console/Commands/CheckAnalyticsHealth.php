<?php

namespace App\Console\Commands;

use App\Models\Analytics\AnalyticsDailySummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckAnalyticsHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check analytics sync status and data freshness';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Checking Analytics Health...');
        $this->newLine();

        // Check last sync date for each tenant
        $tenants = DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->pluck('username')
            ->toArray();

        $this->info("Found " . count($tenants) . " tenant(s)");
        $this->newLine();

        $yesterday = Carbon::yesterday();
        $twoDaysAgo = Carbon::yesterday()->subDay();

        $healthyCount = 0;
        $staleCount = 0;
        $missingCount = 0;

        foreach ($tenants as $tenantId) {
            // Check if yesterday's data exists
            $yesterdayData = AnalyticsDailySummary::forTenant($tenantId)
                ->forDate($yesterday)
                ->exists();

            // Check if two days ago data exists (should definitely be synced)
            $twoDaysAgoData = AnalyticsDailySummary::forTenant($tenantId)
                ->forDate($twoDaysAgo)
                ->exists();

            if ($twoDaysAgoData && $yesterdayData) {
                $healthyCount++;
                $this->info("✓ {$tenantId}: Data is fresh");
            } elseif ($twoDaysAgoData) {
                $staleCount++;
                $this->warn("⚠ {$tenantId}: Yesterday's data missing (may still be syncing)");
            } else {
                $missingCount++;
                $this->error("✗ {$tenantId}: No recent data found");
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Healthy: {$healthyCount}");
        $this->info("  Stale: {$staleCount}");
        $this->info("  Missing: {$missingCount}");

        // Check cache hit rates (if we have logging)
        $this->newLine();
        $this->info("Cache Statistics:");
        
        $totalRecords = AnalyticsDailySummary::count();
        $this->info("  Total cached records: {$totalRecords}");

        $oldestRecord = AnalyticsDailySummary::orderBy('date', 'asc')->first();
        $newestRecord = AnalyticsDailySummary::orderBy('date', 'desc')->first();

        if ($oldestRecord && $newestRecord) {
            $this->info("  Date range: {$oldestRecord->date->format('Y-m-d')} to {$newestRecord->date->format('Y-m-d')}");
        }

        return Command::SUCCESS;
    }
}
