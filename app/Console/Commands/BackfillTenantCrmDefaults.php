<?php

namespace App\Console\Commands;

use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerType;
use App\Models\User;
use App\Services\TenantCrmBootstrapService;
use Illuminate\Console\Command;

/**
 * Backfills the CRM lookup rows (types, priorities, procedures) that used to be
 * created only as a side effect of loading GET /api/crm. Tenants that never
 * opened the CRM dashboard have none, which leaves customer creation with no
 * type to default to.
 *
 * Deliberately does not touch property_request_auto_customer_settings, so a
 * tenant that disabled auto-create keeps that choice.
 */
class BackfillTenantCrmDefaults extends Command
{
    protected $signature = 'crm:backfill-tenant-defaults
                            {--tenant= : Only process a specific tenant ID}
                            {--dry-run : Report what would be created without writing}
                            {--chunk=200 : Tenants to load per batch}';

    protected $description = 'Create missing default CRM types, priorities and procedures for existing tenants';

    public function __construct(
        private TenantCrmBootstrapService $bootstrap
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        if ($dryRun) {
            $this->warn('DRY RUN - no changes will be written.');
            $this->newLine();
        }

        $query = User::query()->where('account_type', 'tenant');

        if ($tenantId) {
            $query->where('id', (int) $tenantId);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('No tenants matched.');

            return self::SUCCESS;
        }

        $this->info("Processing {$total} tenant(s)...");

        $touched = 0;
        $createdTypes = 0;
        $createdPriorities = 0;
        $createdProcedures = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($tenants) use (
            $dryRun,
            $bar,
            &$touched,
            &$createdTypes,
            &$createdPriorities,
            &$createdProcedures
        ) {
            foreach ($tenants as $tenant) {
                $id = (int) $tenant->id;

                $missingTypes = $this->missingTypes($id);
                $missingPriorities = $this->missingPriorities($id);
                $missingProcedures = $this->missingProcedures($id);
                $missingTotal = $missingTypes + $missingPriorities + $missingProcedures;

                if ($missingTotal > 0) {
                    $touched++;
                    $createdTypes += $missingTypes;
                    $createdPriorities += $missingPriorities;
                    $createdProcedures += $missingProcedures;

                    if (! $dryRun) {
                        $this->bootstrap->ensureDefaultTypes($id);
                        $this->bootstrap->ensureDefaultPriorities($id);
                        $this->bootstrap->ensureDefaultProcedures($id);
                    }
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $verb = $dryRun ? 'Would create' : 'Created';

        $this->table(
            ['Metric', 'Count'],
            [
                ['Tenants scanned', $total],
                ['Tenants needing rows', $touched],
                ["{$verb} types", $createdTypes],
                ["{$verb} priorities", $createdPriorities],
                ["{$verb} procedures", $createdProcedures],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('Dry run only. Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }

    private function missingTypes(int $tenantId): int
    {
        $existing = UserApiCustomerType::where('user_id', $tenantId)->pluck('value')->all();

        return $this->countMissing(array_column(TenantCrmBootstrapService::defaultTypes(), 'value'), $existing);
    }

    private function missingPriorities(int $tenantId): int
    {
        $existing = UserApiCustomerPriority::where('user_id', $tenantId)->pluck('value')->all();

        return $this->countMissing(array_column(TenantCrmBootstrapService::defaultPriorities(), 'value'), $existing);
    }

    private function missingProcedures(int $tenantId): int
    {
        $existing = UserApiCustomerProcedure::where('user_id', $tenantId)->pluck('procedure_name')->all();

        return $this->countMissing(array_column(TenantCrmBootstrapService::defaultProcedures(), 'procedure_name'), $existing);
    }

    /**
     * @param  list<string|int>  $expected
     * @param  list<string|int|null>  $existing
     */
    private function countMissing(array $expected, array $existing): int
    {
        $existing = array_map(static fn ($v) => (string) $v, $existing);

        $missing = 0;
        foreach ($expected as $value) {
            if (! in_array((string) $value, $existing, true)) {
                $missing++;
            }
        }

        return $missing;
    }
}
