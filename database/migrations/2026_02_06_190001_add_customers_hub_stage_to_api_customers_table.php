<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            $table->string('customers_hub_stage_id', 50)->nullable()->after('stage_id');
            $table->timestamp('customers_hub_stage_changed_at')->nullable()->after('customers_hub_stage_id');

            $table->foreign('customers_hub_stage_id')
                ->references('stage_id')
                ->on('customers_hub_stages')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropForeign(['customers_hub_stage_id']);
            $table->dropColumn(['customers_hub_stage_id', 'customers_hub_stage_changed_at']);
        });
    }
};
