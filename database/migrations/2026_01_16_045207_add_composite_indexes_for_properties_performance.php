<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds composite indexes to optimize GET /api/properties endpoint performance:
     * - Index for default sorting (user_id, reorder_featured, reorder)
     * - Index for date range filtering (user_id, created_at)
     * - Index for employee filtering (user_id, created_by)
     *
     * @return void
     */
    public function up()
    {
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $indexName]
            );
            return $result[0]->count > 0;
        };

        // Add composite index for default sorting (reorder_featured DESC, reorder ASC)
        // This optimizes the default sort order used in PropertyController::index()
        if (!$hasIndex('user_properties', 'idx_user_reorder_composite')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'reorder_featured', 'reorder'], 'idx_user_reorder_composite');
            });
        }

        // Add composite index for date range filtering
        // Optimizes queries filtering by user_id and created_at date ranges
        if (!$hasIndex('user_properties', 'idx_user_created_at')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_user_created_at');
            });
        }

        // Add composite index for employee filtering
        // Optimizes queries filtering by user_id and created_by (employee_id filter)
        if (!$hasIndex('user_properties', 'idx_user_created_by')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'created_by'], 'idx_user_created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex('idx_user_reorder_composite');
            $table->dropIndex('idx_user_created_at');
            $table->dropIndex('idx_user_created_by');
        });
    }
};
