<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds composite indexes on users_property_requests to optimize
     * filterOptions distinct/pluck: (user_id, city_id), (user_id, districts_id), (user_id, property_type).
     */
    public function up(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return (int) $result[0]->count > 0;
            } catch (\Throwable $e) {
                return false;
            }
        };

        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        // (user_id, city_id) — for where('user_id',?)->whereNotNull('city_id')->distinct()->pluck('city_id')
        if (!$hasIndex('users_property_requests', 'upr_user_city_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'city_id')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'city_id'], 'upr_user_city_index');
                });
            }
        }

        // (user_id, districts_id) — for where('user_id',?)->whereNotNull('districts_id')->distinct()->pluck('districts_id')
        if (!$hasIndex('users_property_requests', 'upr_user_districts_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'districts_id')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'districts_id'], 'upr_user_districts_index');
                });
            }
        }

        // (user_id, property_type) — for where('user_id',?)->whereNotNull('property_type')->distinct()->orderBy('property_type')->pluck('property_type')
        if (!$hasIndex('users_property_requests', 'upr_user_property_type_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'property_type')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'property_type'], 'upr_user_property_type_index');
                });
            }
        }
    }

    public function down(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return (int) $result[0]->count > 0;
            } catch (\Throwable $e) {
                return false;
            }
        };

        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        foreach (['upr_user_city_index', 'upr_user_districts_index', 'upr_user_property_type_index'] as $name) {
            if ($hasIndex('users_property_requests', $name)) {
                Schema::table('users_property_requests', function (Blueprint $table) use ($name) {
                    $table->dropIndex($name);
                });
            }
        }
    }
};
