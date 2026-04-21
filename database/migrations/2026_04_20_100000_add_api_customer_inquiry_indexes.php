<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        try {
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $indexName]
            );

            return (int) $result[0]->count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('api_customer_inquiry')) {
            return;
        }

        // Index for deduplication subquery: MAX(id) grouped by customer_id scoped by user_id
        if (!$this->hasIndex('api_customer_inquiry', 'aci_user_customer_idx')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->index(['user_id', 'customer_id'], 'aci_user_customer_idx');
            });
        }

        // Index for stage filtering in excludeStages and stages filters
        if (!$this->hasIndex('api_customer_inquiry', 'aci_user_stage_idx')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->index(['user_id', 'stage_id'], 'aci_user_stage_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('api_customer_inquiry')) {
            return;
        }

        foreach (['aci_user_customer_idx', 'aci_user_stage_idx'] as $indexName) {
            if ($this->hasIndex('api_customer_inquiry', $indexName)) {
                Schema::table('api_customer_inquiry', function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }
};
