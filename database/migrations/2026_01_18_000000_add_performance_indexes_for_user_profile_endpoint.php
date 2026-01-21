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
     * Adds indexes to optimize GET /api/user endpoint performance:
     * - api_domains_settings(user_id, status) for active domain lookup
     * - users(tenant_id, active) for employee count queries
     *
     * @return void
     */
    public function up()
    {
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return $result[0]->count > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Composite index for api_domains_settings (user_id, status) for active domain lookup
        // This optimizes the query: WHERE user_id = ? AND status = 'active'
        if (Schema::hasTable('api_domains_settings')) {
            if (!$hasIndex('api_domains_settings', 'idx_domains_user_status')) {
                Schema::table('api_domains_settings', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'idx_domains_user_status');
                });
            }
        }

        // Composite index for users (tenant_id, active) for employee count queries
        // This optimizes the queries: WHERE tenant_id = ? AND active = true
        if (Schema::hasTable('users')) {
            if (!$hasIndex('users', 'idx_users_tenant_active')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->index(['tenant_id', 'active'], 'idx_users_tenant_active');
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
        if (Schema::hasTable('api_domains_settings')) {
            Schema::table('api_domains_settings', function (Blueprint $table) {
                $table->dropIndex('idx_domains_user_status');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_tenant_active');
            });
        }
    }
};
