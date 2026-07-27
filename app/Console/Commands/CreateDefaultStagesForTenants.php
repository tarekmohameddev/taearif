<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserPropertyRequest;
use App\Services\TenantCrmBootstrapService;
use Illuminate\Console\Command;

class CreateDefaultStagesForTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property-requests:create-default-stages 
                            {--tenant= : Create stages for a specific tenant ID}
                            {--unlinked-only : Only create for tenants with unlinked property requests}
                            {--dry-run : Show what would be created without making changes}
                            {--auto-settings : Also create/update property request settings with default_stage_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create default customer stages for tenants that are missing them';

    public function __construct(
        private TenantCrmBootstrapService $bootstrap
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $unlinkedOnly = (bool) $this->option('unlinked-only');
        $dryRun = (bool) $this->option('dry-run');
        $autoSettings = (bool) $this->option('auto-settings');

        $this->info('Creating default customer stages for tenants...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        $tenantsToProcess = $this->getTenantsToProcess($tenantId, $unlinkedOnly);

        if (empty($tenantsToProcess)) {
            $this->warn('No tenants found to process.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($tenantsToProcess) . ' tenant(s) to process.');
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $settingsUpdated = 0;
        $results = [];

        foreach ($tenantsToProcess as $tenant) {
            $id = (int) $tenant->id;
            $tenantName = $tenant->name ?? $tenant->email ?? "ID: {$id}";

            $existingStages = UserApiCustomerStage::where('user_id', $id)
                ->where('is_active', true)
                ->count();

            $stagesCreatedCount = 0;

            if ($existingStages > 0) {
                $this->line("Tenant {$tenantName} (ID: {$id}) already has {$existingStages} active stage(s) - skipping stage creation");
                $skipped++;
            } else {
                if (!$dryRun) {
                    $this->bootstrap->ensureDefaultStages($id);
                    $stagesCreatedCount = count(TenantCrmBootstrapService::defaultStages());
                } else {
                    $stagesCreatedCount = count(TenantCrmBootstrapService::defaultStages());
                }
                $created++;
                $this->info("Tenant {$tenantName} (ID: {$id}):");
                if ($dryRun) {
                    $this->line("   Would create {$stagesCreatedCount} stage(s)");
                } else {
                    $this->line("   Created {$stagesCreatedCount} stage(s)");
                }
            }

            $settingsCreated = 'No';
            $firstStageId = UserApiCustomerStage::where('user_id', $id)
                ->where('is_active', true)
                ->orderBy('order')
                ->value('id');

            // In dry-run with no stages yet, simulate first stage id
            if ($dryRun && $firstStageId === null && $stagesCreatedCount > 0) {
                $firstStageId = '?';
            }

            if ($autoSettings) {
                if ($firstStageId === null || $firstStageId === '?') {
                    if (!$dryRun) {
                        $this->warn("   No active stage for tenant {$id}; cannot set auto-customer settings.");
                    } else {
                        $settingsCreated = 'Yes';
                        $settingsUpdated++;
                        $this->line('   Would set property_request_auto_customer_settings');
                    }
                } else {
                    if (!$dryRun) {
                        $this->bootstrap->ensureAutoCustomerSettings($id);
                        $settingsUpdated++;
                        $settingsCreated = 'Yes';
                        $firstStageId = PropertyRequestAutoCustomerSetting::where('user_id', $id)->value('default_stage_id')
                            ?? $firstStageId;
                    } else {
                        $settingsUpdated++;
                        $settingsCreated = 'Yes';
                    }
                    $this->line("   property_request_auto_customer_settings set with default_stage_id: {$firstStageId}");
                }
            }

            if ($stagesCreatedCount > 0 || $settingsCreated === 'Yes') {
                $results[] = [
                    'tenant_id' => $id,
                    'tenant_name' => $tenantName,
                    'stages_created' => $stagesCreatedCount,
                    'first_stage_id' => $firstStageId ?? '-',
                    'settings_created' => $settingsCreated,
                ];
            }

            $this->newLine();
        }

        $this->info('Summary:');
        $this->table(
            ['Tenant ID', 'Tenant Name', 'Stages Created', 'First Stage ID', 'Settings Created'],
            array_map(function ($row) {
                return [
                    $row['tenant_id'],
                    $row['tenant_name'],
                    $row['stages_created'],
                    $row['first_stage_id'],
                    $row['settings_created'],
                ];
            }, $results)
        );

        $this->newLine();
        $this->line('Total processed: ' . count($tenantsToProcess));
        $this->line("Stages created for: {$created} tenant(s)");
        $this->line("Skipped (already have stages): {$skipped} tenant(s)");
        if ($autoSettings) {
            $this->line("Settings created/updated: {$settingsUpdated} tenant(s)");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Get tenants to process based on options
     */
    private function getTenantsToProcess(?string $tenantId, bool $unlinkedOnly): array
    {
        if ($tenantId) {
            $tenant = User::where('account_type', 'tenant')
                ->where('id', $tenantId)
                ->first();

            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return [];
            }

            return [$tenant];
        }

        if ($unlinkedOnly) {
            $tenantIds = UserPropertyRequest::whereNotNull('phone')
                ->whereNull('customer_id')
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            return User::where('account_type', 'tenant')
                ->whereIn('id', $tenantIds)
                ->get()
                ->all();
        }

        return User::where('account_type', 'tenant')->get()->all();
    }
}
