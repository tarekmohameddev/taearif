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

        // Index for memberships query: user_id + status + id (for ordering)
        if (Schema::hasTable('memberships')) {
            if (!$hasIndex('memberships', 'memberships_user_status_id_index')) {
                Schema::table('memberships', function (Blueprint $table) {
                    $table->index(['user_id', 'status', 'id'], 'memberships_user_status_id_index');
                });
            }
        }

        // Composite index for api_menu_items queries: user_id + url + is_active
        if (Schema::hasTable('api_menu_items')) {
            if (!$hasIndex('api_menu_items', 'api_menu_items_user_url_active_index')) {
                Schema::table('api_menu_items', function (Blueprint $table) {
                    $table->index(['user_id', 'url', 'is_active'], 'api_menu_items_user_url_active_index');
                });
            }
        }

        // Index for api_affiliate_users if needed
        if (Schema::hasTable('api_affiliate_users')) {
            if (!$hasIndex('api_affiliate_users', 'api_affiliate_users_user_id_index')) {
                Schema::table('api_affiliate_users', function (Blueprint $table) {
                    $table->index('user_id', 'api_affiliate_users_user_id_index');
                });
            }
        }
    }

    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropIndex('memberships_user_status_id_index');
        });

        Schema::table('api_menu_items', function (Blueprint $table) {
            $table->dropIndex('api_menu_items_user_url_active_index');
        });

        Schema::table('api_affiliate_users', function (Blueprint $table) {
            $table->dropIndex('api_affiliate_users_user_id_index');
        });
    }
};
