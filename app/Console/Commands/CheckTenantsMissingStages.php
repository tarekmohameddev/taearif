<?php

namespace App\Console\Commands;

use App\Models\Api\UserPropertyRequest;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTenantsMissingStages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'property-requests:check-missing-stages 
                            {--tenant= : Check a specific tenant by ID}
                            {--unlinked-only : Only check tenants with unlinked property requests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check which tenants are missing active customer stages (needed for property request customer creation)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $unlinkedOnly = (bool) $this->option('unlinked-only');

        $this->info('Checking tenants for missing customer stages...');
        $this->newLine();

        if ($tenantId) {
            // Check specific tenant
            $tenant = User::where('account_type', 'tenant')
                ->where('id', $tenantId)
                ->first();

            if (!$tenant) {
                $this->error("Tenant with ID {$tenantId} not found.");
                return self::FAILURE;
            }

            $tenantIds = [$tenantId];
            $this->info("Checking specific tenant: {$tenant->name} (ID: {$tenantId})");
        } elseif ($unlinkedOnly) {
            // Get tenants from property requests that don't have linked customers
            $tenantIds = UserPropertyRequest::whereNotNull('phone')
                ->whereNull('customer_id')
                ->distinct()
                ->pluck('user_id')
                ->toArray();

            $this->info('Checking tenants with unlinked property requests only...');
            $this->info("Found " . count($tenantIds) . " unique tenant(s) with unlinked property requests.");
        } else {
            // Get all tenants (users with account_type='tenant' based on memory)
            $tenantIds = User::where('account_type', 'tenant')
                ->pluck('id')
                ->toArray();

            $this->info('Checking all tenants...');
            $this->info("Found " . count($tenantIds) . " tenant(s) total.");
        }

        $this->newLine();

        if (empty($tenantIds)) {
            $this->warn('No tenants found to check.');
            return self::SUCCESS;
        }

        $results = [];
        $missingStages = [];
        $hasSettings = [];
        $hasStages = [];

        foreach ($tenantIds as $tenantId) {
            // Check if tenant has active stages
            $activeStages = UserApiCustomerStage::where('user_id', $tenantId)
                ->where('is_active', true)
                ->count();

            // Check if tenant has settings with default_stage_id
            $settings = PropertyRequestAutoCustomerSetting::where('user_id', $tenantId)->first();
            $hasDefaultStageInSettings = $settings && $settings->default_stage_id;

            // Get tenant info
            $tenant = User::find($tenantId);
            $tenantName = $tenant ? ($tenant->name ?? $tenant->email ?? "ID: {$tenantId}") : "ID: {$tenantId}";

            // Count unlinked property requests for this tenant
            $unlinkedRequests = UserPropertyRequest::where('user_id', $tenantId)
                ->whereNotNull('phone')
                ->whereNull('customer_id')
                ->count();

            $hasStage = $activeStages > 0;
            $canCreateCustomers = $hasDefaultStageInSettings || $hasStage;

            $results[] = [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'active_stages' => $activeStages,
                'has_settings' => $hasDefaultStageInSettings ? 'Yes' : 'No',
                'default_stage_id' => $hasDefaultStageInSettings ? $settings->default_stage_id : 'N/A',
                'can_create' => $canCreateCustomers ? 'Yes' : 'No',
                'unlinked_requests' => $unlinkedRequests,
            ];

            if (!$canCreateCustomers) {
                $missingStages[] = [
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenantName,
                    'unlinked_requests' => $unlinkedRequests,
                ];
            }

            if ($hasDefaultStageInSettings) {
                $hasSettings[] = $tenantId;
            }

            if ($hasStage) {
                $hasStages[] = $tenantId;
            }
        }

        // Display full results table
        $this->info('Full Results:');
        $this->table(
            ['Tenant ID', 'Tenant Name', 'Active Stages', 'Has Settings', 'Default Stage ID', 'Can Create', 'Unlinked Requests'],
            array_map(function ($row) {
                return [
                    $row['tenant_id'],
                    $row['tenant_name'],
                    $row['active_stages'],
                    $row['has_settings'],
                    $row['default_stage_id'],
                    $row['can_create'],
                    $row['unlinked_requests'],
                ];
            }, $results)
        );

        $this->newLine();

        // Summary
        $this->info('Summary:');
        $this->line("Total tenants checked: " . count($tenantIds));
        $this->line("Tenants with active stages: " . count($hasStages));
        $this->line("Tenants with settings configured: " . count($hasSettings));
        $this->line("Tenants missing stages (cannot create customers): " . count($missingStages));

        if (!empty($missingStages)) {
            $this->newLine();
            $this->warn('⚠️  Tenants Missing Stages:');
            $this->table(
                ['Tenant ID', 'Tenant Name', 'Unlinked Requests'],
                array_map(function ($row) {
                    return [
                        $row['tenant_id'],
                        $row['tenant_name'],
                        $row['unlinked_requests'],
                    ];
                }, $missingStages)
            );

            $this->newLine();
            $this->comment('These tenants need:');
            $this->line('  1. At least one active customer stage (users_api_customers_stages), OR');
            $this->line('  2. A property_request_auto_customer_settings record with default_stage_id');
        } else {
            $this->newLine();
            $this->info('✅ All checked tenants have stages configured!');
        }

        return self::SUCCESS;
    }
}

