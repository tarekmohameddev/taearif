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
    protected $signature = 'property-requests:create-default-stages 
                            {--tenant= : Create stages for a specific tenant ID}
                            {--unlinked-only : Only create for tenants with unlinked property requests}
                            {--dry-run : Show what would be created without making changes}
                            {--auto-settings : Also create/update property request settings with default_stage_id}';

    protected $description = 'Create default customer stages for tenants that are missing them';

    public function __construct(
        private TenantCrmBootstrapService $bootstrap
    ) {
        parent::__construct();
    }

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

        $stagesCreatedTotal = 0;
        $settingsUpdatedTotal = 0;
        $results = [];

        foreach ($tenantsToProcess as $tenant) {
            $id = (int) $tenant->id;
            $tenantName = $tenant->name ?? $tenant->email ?? "ID: {$id}";

            $stagesBefore = UserApiCustomerStage::where('user_id', $id)
                ->where('is_active', true)
                ->count();

            $settingsBefore = PropertyRequestAutoCustomerSetting::where('user_id', $id)->first();
            $defaultStageIdBefore = $settingsBefore?->default_stage_id;

            $wouldCreateStages = $stagesBefore === 0;
            $wouldUpdateSettings = $autoSettings && $this->canUpsertAutoCustomerSettings($id, $wouldCreateStages);

            if ($dryRun) {
                if ($wouldCreateStages) {
                    $stagesCreated = count(TenantCrmBootstrapService::defaultStages());
                    $stagesCreatedTotal += $stagesCreated;
                    $this->info("Tenant {$tenantName} (ID: {$id}):");
                    $this->line("   Would create {$stagesCreated} stage(s)");
                } else {
                    $this->line("Tenant {$tenantName} (ID: {$id}) already has {$stagesBefore} active stage(s) - skipping stage creation");
                }

                if ($autoSettings) {
                    if ($wouldUpdateSettings) {
                        $settingsUpdatedTotal++;
                        $this->line('   Would set property_request_auto_customer_settings');
                    } elseif ($stagesBefore === 0 && !$wouldCreateStages) {
                        $this->warn("   No active stage for tenant {$id}; cannot set auto-customer settings.");
                    }
                }

                if ($wouldCreateStages || ($autoSettings && $wouldUpdateSettings)) {
                    $firstStageId = $wouldCreateStages
                        ? '?'
                        : (string) (UserApiCustomerStage::where('user_id', $id)
                            ->where('is_active', true)
                            ->orderBy('order')
                            ->value('id') ?? '-');
                    $results[] = $this->resultRow(
                        $id,
                        $tenantName,
                        $wouldCreateStages ? count(TenantCrmBootstrapService::defaultStages()) : 0,
                        $firstStageId ?? '-',
                        $autoSettings && $wouldUpdateSettings ? 'Yes' : 'No'
                    );
                }

                $this->newLine();
                continue;
            }

            if ($autoSettings) {
                $this->bootstrap->ensureForTenant($id);
            } else {
                $this->bootstrap->ensureDefaultStages($id);
            }

            $stagesAfter = UserApiCustomerStage::where('user_id', $id)
                ->where('is_active', true)
                ->count();
            $stagesCreated = max(0, $stagesAfter - $stagesBefore);

            $settingsAfter = PropertyRequestAutoCustomerSetting::where('user_id', $id)->first();
            $settingsUpdated = $autoSettings && (
                $settingsBefore === null
                || $settingsAfter?->auto_create_customer !== $settingsBefore->auto_create_customer
                || $settingsAfter?->default_stage_id !== $defaultStageIdBefore
            );

            if ($stagesCreated > 0) {
                $stagesCreatedTotal += $stagesCreated;
                $this->info("Tenant {$tenantName} (ID: {$id}):");
                $this->line("   Created {$stagesCreated} stage(s)");
            } elseif ($stagesBefore > 0) {
                $this->line("Tenant {$tenantName} (ID: {$id}) already has {$stagesBefore} active stage(s) - skipping stage creation");
            }

            if ($settingsUpdated) {
                $settingsUpdatedTotal++;
                $stageId = $settingsAfter?->default_stage_id ?? UserApiCustomerStage::where('user_id', $id)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id');
                $this->line("   property_request_auto_customer_settings set with default_stage_id: {$stageId}");
            } elseif ($autoSettings && $stagesAfter === 0) {
                $this->warn("   No active stage for tenant {$id}; cannot set auto-customer settings.");
            }

            if ($stagesCreated > 0 || $settingsUpdated) {
                $results[] = $this->resultRow(
                    $id,
                    $tenantName,
                    $stagesCreated,
                    (string) ($settingsAfter?->default_stage_id ?? '-'),
                    $settingsUpdated ? 'Yes' : 'No'
                );
            }

            $this->newLine();
        }

        $this->info('Summary:');
        $this->table(
            ['Tenant ID', 'Tenant Name', 'Stages Created', 'First Stage ID', 'Settings Created'],
            array_map(fn ($row) => [
                $row['tenant_id'],
                $row['tenant_name'],
                $row['stages_created'],
                $row['first_stage_id'],
                $row['settings_created'],
            ], $results)
        );

        $this->newLine();
        $this->line('Total processed: ' . count($tenantsToProcess));
        $this->line("Stages created (total new rows): {$stagesCreatedTotal}");
        if ($autoSettings) {
            $this->line("Settings created/updated: {$settingsUpdatedTotal} tenant(s)");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

  /**
     * Whether auto-customer settings can be upserted (needs at least one active stage, or stages would be created).
     */
    private function canUpsertAutoCustomerSettings(int $tenantId, bool $wouldCreateStages): bool
    {
        if ($wouldCreateStages) {
            return true;
        }

        return UserApiCustomerStage::where('user_id', $tenantId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * @return array{tenant_id: int, tenant_name: string, stages_created: int, first_stage_id: string, settings_created: string}
     */
    private function resultRow(int $id, string $tenantName, int $stagesCreated, string $firstStageId, string $settingsCreated): array
    {
        return [
            'tenant_id' => $id,
            'tenant_name' => $tenantName,
            'stages_created' => $stagesCreated,
            'first_stage_id' => $firstStageId,
            'settings_created' => $settingsCreated,
        ];
    }

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
