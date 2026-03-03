<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add customers_hub_stage_id to users_property_requests for pipeline (customers_hub_stages).
     * Backfill from customers_hub_stages.id = 1 when that row exists; otherwise leave null (Unassigned).
     */
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
                $table->string('customers_hub_stage_id', 50)->nullable()->after('status_id');
                $table->foreign('customers_hub_stage_id')
                    ->references('stage_id')
                    ->on('customers_hub_stages')
                    ->onDelete('set null');
                $table->index('customers_hub_stage_id');
            }
        });

        $defaultStageId = DB::table('customers_hub_stages')->where('id', 1)->where('is_active', true)->value('stage_id');
        if ($defaultStageId !== null) {
            DB::table('users_property_requests')
                ->whereNull('customers_hub_stage_id')
                ->update(['customers_hub_stage_id' => $defaultStageId]);
        }
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
                $table->dropForeign(['customers_hub_stage_id']);
                $table->dropIndex(['customers_hub_stage_id']);
                $table->dropColumn('customers_hub_stage_id');
            }
        });
    }
};
