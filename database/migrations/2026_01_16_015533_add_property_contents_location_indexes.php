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
     * Adds composite indexes to optimize location-based filtering queries on user_property_contents table:
     * - (property_id, city_id) - for city filtering
     * - (property_id, state_id) - for district/state filtering
     * - (property_id, city_id, state_id) - for combined location filtering
     *
     * These indexes optimize JOIN queries replacing whereHas subqueries in PropertyController::index()
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

        // Composite index for city filtering
        if (!$hasIndex('user_property_contents', 'idx_prop_content_city')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'city_id'], 'idx_prop_content_city');
            });
        }

        // Composite index for district/state filtering
        if (!$hasIndex('user_property_contents', 'idx_prop_content_state')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'state_id'], 'idx_prop_content_state');
            });
        }

        // Composite index for combined location filtering
        if (!$hasIndex('user_property_contents', 'idx_prop_content_location')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'city_id', 'state_id'], 'idx_prop_content_location');
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
            // Drop indexes only if they exist
            try {
                $table->dropIndex('idx_prop_content_city');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }

            try {
                $table->dropIndex('idx_prop_content_state');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }

            try {
                $table->dropIndex('idx_prop_content_location');
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        });
    }
};
