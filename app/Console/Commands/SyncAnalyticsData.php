<?php

namespace App\Console\Commands;

use App\Services\Analytics\Ga4AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAnalyticsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:sync 
                            {--tenant= : Sync specific tenant only}
                            {--date= : Sync specific date (Y-m-d, defaults to yesterday)}
                            {--backfill : Sync last 7 days for initial setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Analytics GA4 page_view events to local database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Ga4AnalyticsService $service): int
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday(); // GA4 has 24-48h delay
            
        if ($this->option('backfill')) {
            $this->info('Starting backfill sync for last 7 days...');
            // Sync last 7 days
            for ($i = 0; $i < 7; $i++) {
                $syncDate = $date->copy()->subDays($i);
                $this->info("\nSyncing date: {$syncDate->format('Y-m-d')} (" . ($i + 1) . "/7)");
                $this->syncDate($service, $syncDate);
            }
            $this->info("\nBackfill sync completed!");
        } else {
            $this->info("Syncing data for date: {$date->format('Y-m-d')}");
            $this->syncDate($service, $date);
            $this->info('Sync completed!');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Sync data for a specific date
     */
    private function syncDate(Ga4AnalyticsService $service, Carbon $date): void
    {
        try {
            if ($this->option('tenant')) {
                // Sync specific tenant
                $tenantId = $this->option('tenant');
                $this->info("Syncing page views for tenant: {$tenantId}");
                $rowsProcessed = $service->syncPageViews($date, $tenantId);
                $this->info("Synced {$rowsProcessed} page view records");
                
                $this->info("Syncing daily summary for tenant: {$tenantId}");
                $service->syncDailySummary($date, $tenantId);
                $this->info("Daily summary synced successfully");
            } else {
                // Sync all tenants
                $this->info("Syncing data for all tenants...");
                $summary = $service->syncAllTenants($date);
                
                $this->info("Sync Summary:");
                $this->info("  Total tenants: {$summary['total']}");
                $this->info("  Successful: {$summary['success']}");
                $this->info("  Errors: {$summary['errors']}");
                
                if (!empty($summary['errors_detail'])) {
                    $this->warn("\nError Details:");
                    foreach ($summary['errors_detail'] as $error) {
                        $this->warn("  - {$error['tenant_id']}: {$error['error']}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync analytics data', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error("Failed to sync analytics data: {$e->getMessage()}");
            throw $e;
        }
    }
}
