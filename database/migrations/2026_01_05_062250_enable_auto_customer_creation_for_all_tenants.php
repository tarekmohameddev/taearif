<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerStage;
use App\Services\PropertyRequestCustomerService;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enable auto-customer creation by default for all existing tenants.
     *
     * @return void
     */
    public function up()
    {
        // Only proceed if tables exist
        if (!Schema::hasTable('users') || !Schema::hasTable('property_request_auto_customer_settings')) {
            return;
        }

        // Get all tenants
        $tenants = User::where('account_type', 'tenant')->get();

        $enabledCount = 0;
        $skippedCount = 0;

        foreach ($tenants as $tenant) {
            // Get the first active customer stage for this tenant
            $firstActiveStage = UserApiCustomerStage::where('user_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('order', 'asc')
                ->first();

            // Skip tenants that don't have any active stages
            if (!$firstActiveStage) {
                $skippedCount++;
                continue;
            }

            // Create or update settings to enable auto-customer creation
            PropertyRequestAutoCustomerSetting::updateOrCreate(
                ['user_id' => $tenant->id],
                [
                    'auto_create_customer' => true,
                    'default_stage_id' => $firstActiveStage->id,
                ]
            );

            // Clear cache for this tenant
            PropertyRequestCustomerService::clearSettingsCache($tenant->id);

            $enabledCount++;
        }

        // Log results (optional - you can remove this if you don't want logs)
        if (function_exists('logger')) {
            logger()->info('Auto-customer creation enabled for tenants', [
                'enabled' => $enabledCount,
                'skipped' => $skippedCount,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     * This will disable auto-customer creation for all tenants (set auto_create_customer to false).
     * Note: We keep the settings records but disable the feature.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('property_request_auto_customer_settings')) {
            return;
        }

        // Disable auto-customer creation for all tenants
        PropertyRequestAutoCustomerSetting::query()->update([
            'auto_create_customer' => false,
        ]);

        // Clear cache for all tenants (in batches to avoid memory issues)
        $tenantIds = User::where('account_type', 'tenant')->pluck('id');
        
        foreach ($tenantIds as $tenantId) {
            PropertyRequestCustomerService::clearSettingsCache($tenantId);
        }
    }
};
