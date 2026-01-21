<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

        // (user_id, status_id) — for statusCountsQuery and status filtering
        if (!$hasIndex('users_property_requests', 'upr_user_status_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'status_id')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'status_id'], 'upr_user_status_index');
                });
            }
        }

        // (user_id, id) — for WHERE user_id=? ORDER BY id DESC LIMIT n
        if (!$hasIndex('users_property_requests', 'upr_user_id_desc_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'id'], 'upr_user_id_desc_index');
                });
            }
        }

        // (user_id, phone) — for COUNT(DISTINCT phone) scoped by user_id
        if (!$hasIndex('users_property_requests', 'upr_user_phone_index')) {
            if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'phone')) {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'phone'], 'upr_user_phone_index');
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

        foreach (['upr_user_status_index', 'upr_user_id_desc_index', 'upr_user_phone_index'] as $name) {
            if ($hasIndex('users_property_requests', $name)) {
                Schema::table('users_property_requests', function (Blueprint $table) use ($name) {
                    $table->dropIndex($name);
                });
            }
        }
    }
};
