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

        // Add indexes on user_property_requests for common filter combinations
        if (!$hasIndex('user_property_requests', 'user_property_requests_user_id_index')) {
            Schema::table('user_property_requests', function (Blueprint $table) {
                $table->index('user_id', 'user_property_requests_user_id_index');
            });
        }

        if (!$hasIndex('user_property_requests', 'user_property_requests_city_id_index')) {
            Schema::table('user_property_requests', function (Blueprint $table) {
                $table->index('city_id', 'user_property_requests_city_id_index');
            });
        }

        if (!$hasIndex('user_property_requests', 'user_property_requests_districts_id_index')) {
            Schema::table('user_property_requests', function (Blueprint $table) {
                $table->index('districts_id', 'user_property_requests_districts_id_index');
            });
        }

        if (!$hasIndex('user_property_requests', 'user_property_requests_category_id_index')) {
            Schema::table('user_property_requests', function (Blueprint $table) {
                $table->index('category_id', 'user_property_requests_category_id_index');
            });
        }

        // Composite index for common filter combination
        if (!$hasIndex('user_property_requests', 'user_property_requests_user_created_index')) {
            Schema::table('user_property_requests', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'user_property_requests_user_created_index');
            });
        }
    }

    public function down()
    {
        Schema::table('user_property_requests', function (Blueprint $table) {
            $table->dropIndex('user_property_requests_user_id_index');
            $table->dropIndex('user_property_requests_city_id_index');
            $table->dropIndex('user_property_requests_districts_id_index');
            $table->dropIndex('user_property_requests_category_id_index');
            $table->dropIndex('user_property_requests_user_created_index');
        });
    }
};

