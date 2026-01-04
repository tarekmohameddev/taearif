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
     * Adds indexes to optimize membership queries:
     * - Composite index on memberships (user_id, expire_date) for RequireActiveMembership middleware
     * - Composite index on users (tenant_id, account_type) for employee lookup queries
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

        // Composite index on memberships table for middleware query optimization
        // Used in: RequireActiveMembership middleware (WHERE user_id = ? ORDER BY expire_date DESC)
        // Note: If memberships_user_expire_index already exists from previous migration, this creates a duplicate
        // but with our specified naming convention. Both indexes serve the same purpose.
        if (Schema::hasTable('memberships')) {
            if (!$hasIndex('memberships', 'idx_memberships_user_expire')) {
                Schema::table('memberships', function (Blueprint $table) {
                    $table->index(['user_id', 'expire_date'], 'idx_memberships_user_expire');
                });
            }
        }

        // Composite index on users table for employee lookup queries
        // Used in: PropertyController and other places (WHERE tenant_id = ? AND account_type = 'employee')
        if (Schema::hasTable('users')) {
            if (!$hasIndex('users', 'idx_users_tenant_account')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index(['tenant_id', 'account_type'], 'idx_users_tenant_account');
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

        if (Schema::hasTable('memberships') && $hasIndex('memberships', 'idx_memberships_user_expire')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropIndex('idx_memberships_user_expire');
            });
        }

        if (Schema::hasTable('users') && $hasIndex('users', 'idx_users_tenant_account')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_tenant_account');
            });
        }
    }
};
