<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add stage_id (customers_hub_stages) to api_customer_inquiry for pipeline.
     * Pipeline uses this column; existing status_id is not dropped.
     * Backfill from customers_hub_stages.id = 1 when that row exists; otherwise leave null (Unassigned).
     */
    public function up(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
                $table->string('stage_id', 50)->nullable()->after('responsible_employee_id');
                $table->foreign('stage_id')
                    ->references('stage_id')
                    ->on('customers_hub_stages')
                    ->onDelete('set null');
                $table->index('stage_id');
            }
        });

        $defaultStageId = DB::table('customers_hub_stages')->where('id', 1)->where('is_active', true)->value('stage_id');
        if ($defaultStageId !== null) {
            DB::table('api_customer_inquiry')
                ->whereNull('stage_id')
                ->update(['stage_id' => $defaultStageId]);
        }
    }

    public function down(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
                $table->dropForeign(['stage_id']);
                $table->dropIndex(['stage_id']);
                $table->dropColumn('stage_id');
            }
        });
    }
};
