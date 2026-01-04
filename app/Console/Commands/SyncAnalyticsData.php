<?php

namespace App\Console\Commands;

use App\Services\Analytics\AnalyticsMaterializationService;
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
                            {--date= : Sync specific date (Y-m-d)}
                            {--backfill : Sync last 7 days for initial setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Google Analytics data to local database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(AnalyticsMaterializationService $service): int
    {
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday(); // GA4 has 24-48h delay
            
        $tenants = $this->option('tenant')
            ? [$this->option('tenant')]
            : $this->getAllTenants($service);
            
        if ($this->option('backfill')) {
            $this->info('Starting backfill sync for last 7 days...');
            // Sync last 7 days
            for ($i = 0; $i < 7; $i++) {
                $syncDate = $date->copy()->subDays($i);
                $this->info("\nSyncing date: {$syncDate->format('Y-m-d')} (" . ($i + 1) . "/7)");
                $this->syncDateForTenants($service, $tenants, $syncDate);
            }
            $this->info("\nBackfill sync completed!");
        } else {
            $this->info("Syncing data for date: {$date->format('Y-m-d')}");
            $this->syncDateForTenants($service, $tenants, $date);
            $this->info('Sync completed!');
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Get all active tenants
     */
    private function getAllTenants(AnalyticsMaterializationService $service): array
    {
        return $service->getAllTenants();
    }
    
    /**
     * Sync data for all tenants for a specific date
     */
    private function syncDateForTenants(AnalyticsMaterializationService $service, array $tenants, Carbon $date): void
    {
        $total = count($tenants);
        $this->info("Processing {$total} tenant(s) for date: {$date->format('Y-m-d')}...");
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($tenants as $tenantId) {
            try {
                $service->materializeTenantData($tenantId, $date);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                Log::error('Failed to sync tenant data', [
                    'tenant_id' => $tenantId,
                    'date' => $date->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
                $this->warn("\nFailed to sync tenant: {$tenantId}");
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Completed: {$successCount} successful, {$errorCount} failed");
    }
}
