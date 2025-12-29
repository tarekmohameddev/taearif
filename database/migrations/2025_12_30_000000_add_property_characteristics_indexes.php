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

        // Index on user_property_characteristics.property_id for whereHas queries
        if (!$hasIndex('user_property_characteristics', 'user_property_characteristics_property_id_index')) {
            Schema::table('user_property_characteristics', function (Blueprint $table) {
                $table->index('property_id', 'user_property_characteristics_property_id_index');
            });
        }

        // Composite indexes for common filter combinations
        $commonFilters = ['private_parking', 'elevator', 'annex', 'garden', 'balcony'];
        foreach ($commonFilters as $filter) {
            $indexName = "idx_prop_char_{$filter}";
            if (!$hasIndex('user_property_characteristics', $indexName)) {
                Schema::table('user_property_characteristics', function (Blueprint $table) use ($filter, $indexName) {
                    $table->index(['property_id', $filter], $indexName);
                });
            }
        }
    }

    public function down()
    {
        Schema::table('user_property_characteristics', function (Blueprint $table) {
            $table->dropIndex('user_property_characteristics_property_id_index');
            $table->dropIndex('idx_prop_char_private_parking');
            $table->dropIndex('idx_prop_char_elevator');
            $table->dropIndex('idx_prop_char_annex');
            $table->dropIndex('idx_prop_char_garden');
            $table->dropIndex('idx_prop_char_balcony');
        });
    }
};

