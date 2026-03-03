<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserPropertyRequest;
use App\Services\PropertyRequestCustomerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

    /**
     * Default stages to create
     */
    private array $defaultStages = [
        ['stage_name' => 'طلب معاينه',        'order' => 1],
        ['stage_name' => 'صفقة بيع او ايجار', 'order' => 2],
        ['stage_name' => 'اقفال الصفقة',      'order' => 3],
    ];

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

        // Get tenants to process
        $tenantsToProcess = $this->getTenantsToProcess($tenantId, $unlinkedOnly);

        if (empty($tenantsToProcess)) {
            $this->warn('No tenants found to process.');
            return self::SUCCESS;
        }

        $this->info("Found " . count($tenantsToProcess) . " tenant(s) to process.");
        $this->newLine();

        $created = 0;
        $skipped = 0;
        $settingsUpdated = 0;
        $results = [];

        foreach ($tenantsToProcess as $tenant) {
            $tenantId = $tenant->id;
            $tenantName = $tenant->name ?? $tenant->email ?? "ID: {$tenantId}";

            // Check if tenant already has active stages
            $existingStages = UserApiCustomerStage::where('user_id', $tenantId)
                ->where('is_active', true)
                ->count();

            if ($existingStages > 0) {
                $this->line("⏭️  Tenant {$tenantName} (ID: {$tenantId}) already has {$existingStages} active stage(s) - skipping");
                $skipped++;
                continue;
            }

            // Create stages
            $stagesCreated = [];
            if (!$dryRun) {
                foreach ($this->defaultStages as $stageData) {
                    $stage = UserApiCustomerStage::create([
                        'user_id'   => $tenantId,
                        'stage_name' => $stageData['stage_name'],
                        'order'     => $stageData['order'],
                        'is_active' => true,
                    ]);
                    $stagesCreated[] = $stage;
                }
            } else {
                // In dry-run, just simulate
                foreach ($this->defaultStages as $stageData) {
                    $stagesCreated[] = (object) [
                        'id' => '?',
                        'stage_name' => $stageData['stage_name'],
                        'order' => $stageData['order'],
                    ];
                }
            }

            $firstStageId = $stagesCreated[0]->id ?? null;

            // Optionally create/update settings
            $settingsCreated = false;
            if ($autoSettings && $firstStageId) {
                if (!$dryRun) {
                    PropertyRequestAutoCustomerSetting::updateOrCreate(
                        ['user_id' => $tenantId],
                        [
                            'auto_create_customer' => true,
                            'default_stage_id' => $firstStageId,
                        ]
                    );
                    PropertyRequestCustomerService::clearSettingsCache($tenantId);
                    $settingsCreated = true;
                    $settingsUpdated++;
                } else {
                    $settingsCreated = true;
                    $settingsUpdated++;
                }
            }

            $created++;
            $results[] = [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'stages_created' => count($stagesCreated),
                'first_stage_id' => $firstStageId,
                'settings_created' => $settingsCreated ? 'Yes' : 'No',
            ];

            $this->info("✅ Tenant {$tenantName} (ID: {$tenantId}):");
            $this->line("   Created " . count($stagesCreated) . " stage(s):");
            foreach ($stagesCreated as $stage) {
                $stageId = is_object($stage) && isset($stage->id) ? $stage->id : '?';
                $this->line("     - {$stage->stage_name} (Order: {$stage->order}, ID: {$stageId})");
            }
            if ($settingsCreated) {
                $this->line("   ✅ Settings created/updated with default_stage_id: {$firstStageId}");
            }
            $this->newLine();
        }

        // Summary
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
        $this->line("Total processed: " . count($tenantsToProcess));
        $this->line("Stages created for: {$created} tenant(s)");
        $this->line("Skipped (already have stages): {$skipped} tenant(s)");
        if ($autoSettings) {
            $this->line("Settings created/updated: {$settingsUpdated} tenant(s)");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn("This was a dry run. Run without --dry-run to apply changes.");
        }

        return self::SUCCESS;
    }

    /**
     * Get tenants to process based on options
     */
    private function getTenantsToProcess(?string $tenantId, bool $unlinkedOnly): array
    {
        if ($tenantId) {
            // Specific tenant
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
            // Get tenants from unlinked property requests
            $tenantIds = UserPropertyRequest::whereNotNull('phone')
                ->whereDoesntHave('customers')
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            return User::where('account_type', 'tenant')
                ->whereIn('id', $tenantIds)
                ->get()
                ->all();
        }

        // All tenants
        return User::where('account_type', 'tenant')->get()->all();
    }
}

