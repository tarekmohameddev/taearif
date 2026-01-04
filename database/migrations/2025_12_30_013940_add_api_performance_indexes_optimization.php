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
     * Adds composite indexes to optimize API performance, specifically for:
     * - CRMController customer filtering by priority, procedure, and type
     * - PropertyController filtering with multiple characteristics
     * - Common query patterns in api_customers and user_properties tables
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

        // ===== api_customers table indexes =====
        // These indexes optimize CRMController queries that filter by priority, procedure, and type
        // Note: user_id + stage_id index already exists from previous migration
        
        if (!$hasIndex('api_customers', 'api_customers_user_priority_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index(['user_id', 'priority_id'], 'api_customers_user_priority_index');
            });
        }

        if (!$hasIndex('api_customers', 'api_customers_user_procedure_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index(['user_id', 'procedure_id'], 'api_customers_user_procedure_index');
            });
        }

        if (!$hasIndex('api_customers', 'api_customers_user_type_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index(['user_id', 'type_id'], 'api_customers_user_type_index');
            });
        }

        // ===== user_properties table indexes =====
        // These indexes optimize PropertyController queries with multiple filters
        // Note: Individual indexes on user_id+purpose, user_id+status, user_id+price, user_id+area already exist
        
        // Composite index for common filter combination: user_id + purpose + status
        if (!$hasIndex('user_properties', 'user_properties_user_purpose_status_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'purpose', 'status'], 'user_properties_user_purpose_status_index');
            });
        }

        // Composite index for price and area filtering together
        if (!$hasIndex('user_properties', 'user_properties_user_price_area_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'price', 'area'], 'user_properties_user_price_area_index');
            });
        }

        // ===== user_property_characteristics table indexes =====
        // Verify and add any missing indexes for characteristic filters
        // Note: property_id index and some composite indexes already exist from previous migration
        
        // Add composite indexes for additional common filter combinations
        $additionalFilters = ['basement', 'majlis', 'storage_room', 'living_room', 'dining_room', 
                             'maid_room', 'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 
                             'floors', 'bathrooms', 'rooms', 'building_age'];
        
        foreach ($additionalFilters as $filter) {
            $indexName = "idx_prop_char_{$filter}";
            if (!$hasIndex('user_property_characteristics', $indexName)) {
                Schema::table('user_property_characteristics', function (Blueprint $table) use ($filter, $indexName) {
                    $table->index(['property_id', $filter], $indexName);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop api_customers indexes
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropIndex('api_customers_user_priority_index');
            $table->dropIndex('api_customers_user_procedure_index');
            $table->dropIndex('api_customers_user_type_index');
        });

        // Drop user_properties indexes
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex('user_properties_user_purpose_status_index');
            $table->dropIndex('user_properties_user_price_area_index');
        });

        // Drop user_property_characteristics indexes
        $additionalFilters = ['basement', 'majlis', 'storage_room', 'living_room', 'dining_room', 
                             'maid_room', 'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 
                             'floors', 'bathrooms', 'rooms', 'building_age'];
        
        Schema::table('user_property_characteristics', function (Blueprint $table) use ($additionalFilters) {
            foreach ($additionalFilters as $filter) {
                $indexName = "idx_prop_char_{$filter}";
                try {
                    $table->dropIndex($indexName);
                } catch (\Exception $e) {
                    // Index might not exist, continue
                }
            }
        });
    }
};
