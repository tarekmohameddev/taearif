<?php

namespace App\Console\Commands;

use App\Models\CustomersHub\CustomersHubStage;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BackfillCustomersHubStage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:backfill-hub-stage
                            {--tenant-id= : Backfill a specific tenant (api_customers.user_id)}
                            {--dry-run : Show counts only, no updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill customers_hub_stage_id = new_lead for api_customers rows that have no hub stage';

    public function handle(): int
    {
        $tenantId = $this->option('tenant-id');
        $dryRun = (bool) $this->option('dry-run');

        $stageId = 'new_lead';
        $stageExists = CustomersHubStage::where('stage_id', $stageId)->where('is_active', true)->exists();
        if (! $stageExists) {
            $this->error("Required stage '{$stageId}' not found or inactive in customers_hub_stages.");
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $baseQuery = DB::table('api_customers')
            ->whereNull('deleted_at')
            ->whereNull('customers_hub_stage_id');

        if ($tenantId !== null && $tenantId !== '') {
            $baseQuery->where('user_id', (int) $tenantId);
        }

        $counts = (clone $baseQuery)
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();

        $total = (int) $counts->sum('cnt');
        if ($total === 0) {
            $this->info('No customers found with customers_hub_stage_id = NULL.');
            return self::SUCCESS;
        }

        $rows = $counts->map(function ($row) {
            return [
                'tenant_id' => (int) $row->user_id,
                'customers_missing_stage' => (int) $row->cnt,
            ];
        })->all();

        $this->info('Customers missing hub stage (by tenant):');
        $this->table(['Tenant ID', 'Customers Missing Stage'], array_map(function (array $r) {
            return [$r['tenant_id'], $r['customers_missing_stage']];
        }, $rows));

        $this->newLine();
        $this->line("Total customers missing stage: {$total}");

        if ($dryRun) {
            return self::SUCCESS;
        }

        $now = Carbon::now();
        $updatedTotal = 0;
        $updatedRows = [];

        foreach ($rows as $r) {
            $tid = (int) $r['tenant_id'];
            $updated = DB::table('api_customers')
                ->where('user_id', $tid)
                ->whereNull('deleted_at')
                ->whereNull('customers_hub_stage_id')
                ->update([
                    'customers_hub_stage_id' => $stageId,
                    'customers_hub_stage_changed_at' => $now,
                    'updated_at' => $now,
                ]);

            $updatedRows[] = [
                'tenant_id' => $tid,
                'updated' => (int) $updated,
            ];
            $updatedTotal += (int) $updated;
        }

        $this->newLine();
        $this->info("Backfill completed. Updated rows: {$updatedTotal}");
        $this->table(['Tenant ID', 'Updated'], array_map(function (array $r) {
            return [$r['tenant_id'], $r['updated']];
        }, $updatedRows));

        return self::SUCCESS;
    }
}

