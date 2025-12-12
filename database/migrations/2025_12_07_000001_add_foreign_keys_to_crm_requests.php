<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_requests')) {
            return;
        }

        $hasFk = fn(string $name) => DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'crm_requests')
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        $hasIndex = fn(string $name) => DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'crm_requests')
            ->where('INDEX_NAME', $name)
            ->exists();

        Schema::table('crm_requests', function (Blueprint $table) use ($hasFk, $hasIndex) {
            // Foreign keys
            if (!$hasFk('crm_requests_user_id_foreign')) {
                $table->foreign('user_id', 'crm_requests_user_id_foreign')
                    ->references('id')->on('users')
                    ->cascadeOnDelete();
            }

            if (!$hasFk('crm_requests_customer_id_foreign')) {
                $table->foreign('customer_id', 'crm_requests_customer_id_foreign')
                    ->references('id')->on('api_customers')
                    ->cascadeOnDelete();
            }

            if (!$hasFk('crm_requests_stage_id_foreign')) {
                $table->foreign('stage_id', 'crm_requests_stage_id_foreign')
                    ->references('id')->on('users_api_customers_stages')
                    ->cascadeOnDelete();
            }

            // Indexes to speed tenant-scoped lookups and ordering
            if (!$hasIndex('crm_requests_customer_user_index')) {
                $table->index(['customer_id', 'user_id'], 'crm_requests_customer_user_index');
            }
            if (!$hasIndex('crm_requests_user_stage_index')) {
                $table->index(['user_id', 'stage_id'], 'crm_requests_user_stage_index');
            }
            if (!$hasIndex('crm_requests_user_stage_position_index')) {
                $table->index(['user_id', 'stage_id', 'position'], 'crm_requests_user_stage_position_index');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_requests')) {
            return;
        }

        $hasFk = fn(string $name) => DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'crm_requests')
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        $hasIndex = fn(string $name) => DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'crm_requests')
            ->where('INDEX_NAME', $name)
            ->exists();

        Schema::table('crm_requests', function (Blueprint $table) use ($hasFk, $hasIndex) {
            if ($hasFk('crm_requests_user_id_foreign')) {
                $table->dropForeign('crm_requests_user_id_foreign');
            }
            if ($hasFk('crm_requests_customer_id_foreign')) {
                $table->dropForeign('crm_requests_customer_id_foreign');
            }
            if ($hasFk('crm_requests_stage_id_foreign')) {
                $table->dropForeign('crm_requests_stage_id_foreign');
            }

            if ($hasIndex('crm_requests_customer_user_index')) {
                $table->dropIndex('crm_requests_customer_user_index');
            }
            if ($hasIndex('crm_requests_user_stage_index')) {
                $table->dropIndex('crm_requests_user_stage_index');
            }
            if ($hasIndex('crm_requests_user_stage_position_index')) {
                $table->dropIndex('crm_requests_user_stage_position_index');
            }
        });
    }
};

