<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_requests')) {
            return;
        }

        Schema::table('crm_requests', function (Blueprint $table) {
            // Foreign keys
            $table->foreign('user_id', 'crm_requests_user_id_foreign')
                ->references('id')->on('users')
                ->cascadeOnDelete();

            $table->foreign('customer_id', 'crm_requests_customer_id_foreign')
                ->references('id')->on('api_customers')
                ->cascadeOnDelete();

            $table->foreign('stage_id', 'crm_requests_stage_id_foreign')
                ->references('id')->on('users_api_customers_stages')
                ->cascadeOnDelete();

            // Indexes to speed tenant-scoped lookups and ordering
            $table->index(['customer_id', 'user_id'], 'crm_requests_customer_user_index');
            $table->index(['user_id', 'stage_id'], 'crm_requests_user_stage_index');
            $table->index(['user_id', 'stage_id', 'position'], 'crm_requests_user_stage_position_index');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_requests')) {
            return;
        }

        Schema::table('crm_requests', function (Blueprint $table) {
            $table->dropForeign('crm_requests_user_id_foreign');
            $table->dropForeign('crm_requests_customer_id_foreign');
            $table->dropForeign('crm_requests_stage_id_foreign');

            $table->dropIndex('crm_requests_customer_user_index');
            $table->dropIndex('crm_requests_user_stage_index');
            $table->dropIndex('crm_requests_user_stage_position_index');
        });
    }
};

