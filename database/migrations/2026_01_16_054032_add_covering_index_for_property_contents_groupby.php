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
     * Adds composite index on (property_id, id) to optimize GROUP BY queries
     * with MIN(id) aggregations when joining user_property_contents to user_properties.
     * This covering index allows MySQL to efficiently find the first content per property
     * without accessing the full table data.
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

        // Add composite index on (property_id, id) for GROUP BY optimization
        // This allows MySQL to efficiently find MIN(id) for each property_id group
        if (!$hasIndex('user_property_contents', 'idx_prop_content_property_id_id')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'id'], 'idx_prop_content_property_id_id');
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
        if (Schema::hasTable('user_property_contents')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->dropIndex('idx_prop_content_property_id_id');
            });
        }
    }
};
