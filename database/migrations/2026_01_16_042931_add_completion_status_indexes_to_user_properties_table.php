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
     * Adds indexes for completion_status column to optimize property cards endpoint queries.
     * These indexes enable efficient filtering by completion_status in combination with user_id and purpose.
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

        // Add single column index on completion_status
        if (!$hasIndex('user_properties', 'idx_up_completion_status')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index('completion_status', 'idx_up_completion_status');
            });
        }

        // Add composite index for user_id + completion_status
        // Optimizes queries filtering by user_id and completion_status
        if (!$hasIndex('user_properties', 'user_properties_user_completion_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'completion_status'], 'user_properties_user_completion_index');
            });
        }

        // Add composite index for user_id + purpose + completion_status
        // Optimizes queries filtering by user_id, purpose, and completion_status (used in cards endpoint)
        if (!$hasIndex('user_properties', 'user_properties_user_purpose_completion_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'purpose', 'completion_status'], 'user_properties_user_purpose_completion_index');
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
            $table->dropIndex('idx_up_completion_status');
            $table->dropIndex('user_properties_user_completion_index');
            $table->dropIndex('user_properties_user_purpose_completion_index');
        });
    }
};
