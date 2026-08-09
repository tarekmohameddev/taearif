<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds an index on user_projects.user_id to speed up quota COUNT
     * queries in ProjectController::store.
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

        if (!Schema::hasTable('user_projects')) {
            return;
        }

        if (!$hasIndex('user_projects', 'user_projects_user_id_index')) {
            if (Schema::hasColumn('user_projects', 'user_id')) {
                Schema::table('user_projects', function (Blueprint $table) {
                    $table->index('user_id', 'user_projects_user_id_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
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

        if (!Schema::hasTable('user_projects')) {
            return;
        }

        if ($hasIndex('user_projects', 'user_projects_user_id_index')) {
            Schema::table('user_projects', function (Blueprint $table) {
                $table->dropIndex('user_projects_user_id_index');
            });
        }
    }
};
