<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
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

        // Add indexes on users_property_requests for common filter combinations
        // Note: category_id and city_id may already have indexes from previous migrations
        // (upr_category_id_idx and upr_city_id_idx), so we check for those too
        
        if (Schema::hasTable('users_property_requests')) {
            // user_id index
            if (!$hasIndex('users_property_requests', 'users_property_requests_user_id_index') && 
                !$hasIndex('users_property_requests', 'users_property_requests_user_id')) {
                if (Schema::hasColumn('users_property_requests', 'user_id')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index('user_id', 'users_property_requests_user_id_index');
                    });
                }
            }

            // city_id may already have index 'upr_city_id_idx' from previous migration
            if (!$hasIndex('users_property_requests', 'users_property_requests_city_id_index') && 
                !$hasIndex('users_property_requests', 'upr_city_id_idx')) {
                if (Schema::hasColumn('users_property_requests', 'city_id')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index('city_id', 'users_property_requests_city_id_index');
                    });
                }
            }

            // districts_id index
            if (!$hasIndex('users_property_requests', 'users_property_requests_districts_id_index')) {
                if (Schema::hasColumn('users_property_requests', 'districts_id')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index('districts_id', 'users_property_requests_districts_id_index');
                    });
                }
            }

            // category_id may already have index 'upr_category_id_idx' from previous migration
            if (!$hasIndex('users_property_requests', 'users_property_requests_category_id_index') && 
                !$hasIndex('users_property_requests', 'upr_category_id_idx')) {
                if (Schema::hasColumn('users_property_requests', 'category_id')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index('category_id', 'users_property_requests_category_id_index');
                    });
                }
            }

            // Composite index for common filter combination
            if (!$hasIndex('users_property_requests', 'users_property_requests_user_created_index')) {
                if (Schema::hasColumn('users_property_requests', 'user_id') && 
                    Schema::hasColumn('users_property_requests', 'created_at')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index(['user_id', 'created_at'], 'users_property_requests_user_created_index');
                    });
                }
            }
        }
    }

    public function down()
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropIndex('users_property_requests_user_id_index');
            $table->dropIndex('users_property_requests_city_id_index');
            $table->dropIndex('users_property_requests_districts_id_index');
            $table->dropIndex('users_property_requests_category_id_index');
            $table->dropIndex('users_property_requests_user_created_index');
        });
    }
};

