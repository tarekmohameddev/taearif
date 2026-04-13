<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        if (! Schema::hasColumn('customers_hub_stages', 'is_global')) {
            Schema::table('customers_hub_stages', function (Blueprint $table) {
                $table->boolean('is_global')->default(true)->after('is_active');
            });
        }

        DB::table('customers_hub_stages')
            ->whereIn('stage_id', ['qualified', 'negotiation'])
            ->update(['is_global' => false]);

        $this->dropHubStageForeignKeys();

        if (DB::table('customers_hub_stages')->where('stage_id', 'closing')->exists()) {
            DB::table('customers_hub_stages')
                ->where('stage_id', 'closing')
                ->update([
                    'stage_id' => 'deal_completed',
                    'stage_name_en' => 'Deal Completed',
                    'stage_name_ar' => 'تم اتمام الصفقة',
                    'color' => '#22c55e',
                    'description' => 'Deal has been successfully completed',
                    'is_global' => true,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('api_customers') && Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
            DB::table('api_customers')
                ->where('customers_hub_stage_id', 'closing')
                ->update(['customers_hub_stage_id' => 'deal_completed']);
        }

        if (Schema::hasTable('users_property_requests') && Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            DB::table('users_property_requests')
                ->where('customers_hub_stage_id', 'closing')
                ->update(['customers_hub_stage_id' => 'deal_completed']);
        }

        if (Schema::hasTable('api_customer_inquiry') && Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            DB::table('api_customer_inquiry')
                ->where('stage_id', 'closing')
                ->update(['stage_id' => 'deal_completed']);
        }

        $this->restoreHubStageForeignKeys();

        if (! DB::table('customers_hub_stages')->where('stage_id', 'deal_rejected')->exists()) {
            DB::table('customers_hub_stages')->insert([
                'stage_id' => 'deal_rejected',
                'stage_name_en' => 'Deal Rejected',
                'stage_name_ar' => 'تم رفض الصفقة',
                'color' => '#ef4444',
                'order' => 5,
                'description' => 'Deal has been rejected',
                'is_active' => true,
                'is_global' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        if (Schema::hasTable('api_customers') && Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
            DB::table('api_customers')
                ->where('customers_hub_stage_id', 'deal_rejected')
                ->update(['customers_hub_stage_id' => null]);
        }
        if (Schema::hasTable('users_property_requests') && Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            DB::table('users_property_requests')
                ->where('customers_hub_stage_id', 'deal_rejected')
                ->update(['customers_hub_stage_id' => null]);
        }
        if (Schema::hasTable('api_customer_inquiry') && Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            DB::table('api_customer_inquiry')
                ->where('stage_id', 'deal_rejected')
                ->update(['stage_id' => null]);
        }

        DB::table('customers_hub_stages')->where('stage_id', 'deal_rejected')->delete();

        $this->dropHubStageForeignKeys();

        if (DB::table('customers_hub_stages')->where('stage_id', 'deal_completed')->exists()) {
            DB::table('customers_hub_stages')
                ->where('stage_id', 'deal_completed')
                ->update([
                    'stage_id' => 'closing',
                    'stage_name_en' => 'Closing',
                    'stage_name_ar' => 'إتمام الصفقة',
                    'description' => 'Final transaction completion',
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('api_customers') && Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
            DB::table('api_customers')
                ->where('customers_hub_stage_id', 'deal_completed')
                ->update(['customers_hub_stage_id' => 'closing']);
        }

        if (Schema::hasTable('users_property_requests') && Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            DB::table('users_property_requests')
                ->where('customers_hub_stage_id', 'deal_completed')
                ->update(['customers_hub_stage_id' => 'closing']);
        }

        if (Schema::hasTable('api_customer_inquiry') && Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            DB::table('api_customer_inquiry')
                ->where('stage_id', 'deal_completed')
                ->update(['stage_id' => 'closing']);
        }

        $this->restoreHubStageForeignKeys();

        DB::table('customers_hub_stages')
            ->whereIn('stage_id', ['qualified', 'negotiation'])
            ->update(['is_global' => true]);

        if (Schema::hasColumn('customers_hub_stages', 'is_global')) {
            Schema::table('customers_hub_stages', function (Blueprint $table) {
                $table->dropColumn('is_global');
            });
        }
    }

    private function dropHubStageForeignKeys(): void
    {
        $drops = [
            ['api_customers', 'customers_hub_stage_id'],
            ['users_property_requests', 'customers_hub_stage_id'],
            ['api_customer_inquiry', 'stage_id'],
        ];

        foreach ($drops as [$tableName, $column]) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $column)) {
                continue;
            }
            try {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropForeign([$column]);
                });
            } catch (\Throwable $e) {
                // FK may not exist or already dropped
            }
        }
    }

    private function restoreHubStageForeignKeys(): void
    {
        if (Schema::hasTable('api_customers')
            && Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
            try {
                Schema::table('api_customers', function (Blueprint $table) {
                    $table->foreign('customers_hub_stage_id')
                        ->references('stage_id')
                        ->on('customers_hub_stages')
                        ->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Already exists
            }
        }

        if (Schema::hasTable('users_property_requests')
            && Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            try {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->foreign('customers_hub_stage_id')
                        ->references('stage_id')
                        ->on('customers_hub_stages')
                        ->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Already exists
            }
        }

        if (Schema::hasTable('api_customer_inquiry')
            && Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            try {
                Schema::table('api_customer_inquiry', function (Blueprint $table) {
                    $table->foreign('stage_id')
                        ->references('stage_id')
                        ->on('customers_hub_stages')
                        ->onDelete('set null');
                });
            } catch (\Throwable $e) {
                // Already exists
            }
        }
    }
};
