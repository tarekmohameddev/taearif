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
     * Adds indexes to optimize search queries on user_property_contents table:
     * - property_id index for whereHas queries (if not already present via foreign key)
     * - title index for LIKE searches in PropertyController::availableUnits()
     * - Composite index on (property_id, title) for optimized whereHas with LIKE searches
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

        // Index on property_id for whereHas queries
        // Note: This may already exist via foreign key constraint, but we add it explicitly if missing
        if (!$hasIndex('user_property_contents', 'user_property_contents_property_id_index')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index('property_id', 'user_property_contents_property_id_index');
            });
        }

        // Index on title for LIKE searches (helps with prefix searches LIKE 'prefix%')
        // Standard B-tree index works well for prefix searches
        if (!$hasIndex('user_property_contents', 'user_property_contents_title_index')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index('title', 'user_property_contents_title_index');
            });
        }

        // Composite index on (property_id, title) for optimized whereHas queries with LIKE searches
        // This index is especially useful for the query pattern in PropertyController::availableUnits()
        // where we use whereHas('contents') with title LIKE search
        if (!$hasIndex('user_property_contents', 'user_property_contents_property_title_index')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'title'], 'user_property_contents_property_title_index');
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
        Schema::table('user_property_contents', function (Blueprint $table) {
            // Drop indexes only if they exist (they may not exist if created by foreign key)
            try {
                $table->dropIndex('user_property_contents_property_id_index');
            } catch (\Exception $e) {
                // Index might not exist or was created by foreign key, continue
            }

            try {
                $table->dropIndex('user_property_contents_title_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }

            try {
                $table->dropIndex('user_property_contents_property_title_index');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        });
    }
};
