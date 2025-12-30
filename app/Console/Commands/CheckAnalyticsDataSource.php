<?php

namespace App\Console\Commands;

use App\Models\Analytics\AnalyticsDailySummary;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckAnalyticsDataSource extends Command
{
    protected $signature = 'analytics:check-source {--tenant= : Check specific tenant}';
    protected $description = 'Check if endpoints will use database or Google Analytics API';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $tenants = $tenantId ? [$tenantId] : $this->getAllTenants();

        $this->info('Checking data source for analytics endpoints...');
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->info("Tenant: <comment>{$tenant}</comment>");
            
            // Check each endpoint type
            $endpoints = [
                'visitors' => 'visitors',
                'devices' => 'devices',
                'traffic_sources' => 'traffic-sources',
                'summary' => 'summary',
                'top_pages' => 'most-visited-pages',
            ];

            $yesterday = Carbon::yesterday();
            $sevenDaysAgo = Carbon::yesterday()->subDays(6);

            foreach ($endpoints as $metricType => $endpointName) {
                $hasData = false;
                $dataSource = 'Google Analytics API (fallback)';
                
                if ($metricType === 'visitors' || $metricType === 'summary') {
                    // These endpoints check date range
                    $records = AnalyticsDailySummary::forTenant($tenant)
                        ->forDateRange($sevenDaysAgo, $yesterday)
                        ->forMetricType($metricType)
                        ->count();
                    
                    $hasData = $records > 0;
                } else {
                    // These endpoints check yesterday's data
                    $record = AnalyticsDailySummary::forTenant($tenant)
                        ->forDate($yesterday)
                        ->forMetricType($metricType)
                        ->exists();
                    
                    $hasData = $record;
                }

                if ($hasData) {
                    $dataSource = 'Database (materialized) ✓';
                    $this->line("  ✓ <info>{$endpointName}</info>: {$dataSource}");
                } else {
                    $this->line("  ✗ <error>{$endpointName}</error>: {$dataSource}");
                }
            }

            $this->newLine();
        }

        // Show statistics
        $totalRecords = AnalyticsDailySummary::count();
        $this->info("Total materialized records in database: <comment>{$totalRecords}</comment>");

        if ($totalRecords > 0) {
            $oldest = AnalyticsDailySummary::orderBy('date', 'asc')->first();
            $newest = AnalyticsDailySummary::orderBy('date', 'desc')->first();
            
            if ($oldest && $newest) {
                $this->info("Date range: <comment>{$oldest->date->format('Y-m-d')}</comment> to <comment>{$newest->date->format('Y-m-d')}</comment>");
            }
        }

        return Command::SUCCESS;
    }

    private function getAllTenants(): array
    {
        return DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->pluck('username')
            ->toArray();
    }
}

