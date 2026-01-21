<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds index on user_districts(city_id) to optimize WHERE city_id = ? in
     * property-requests filters and other district-by-city lookups.
     */
    public function up(): void
    {
        if (!Schema::hasTable('user_districts') || !Schema::hasColumn('user_districts', 'city_id')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'user_districts' AND index_name = 'user_districts_city_id_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) > 0) {
            return;
        }

        Schema::table('user_districts', function (Blueprint $table) {
            $table->index('city_id', 'user_districts_city_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('user_districts')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'user_districts' AND index_name = 'user_districts_city_id_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) === 0) {
            return;
        }

        Schema::table('user_districts', function (Blueprint $table) {
            $table->dropIndex('user_districts_city_id_index');
        });
    }
};
