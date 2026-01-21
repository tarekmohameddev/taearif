<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds index (user_id, order) on users_api_customers_stages to optimize
     * WHERE user_id = ? ORDER BY order in property-requests filters and CRM.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users_api_customers_stages')) {
            return;
        }
        if (!Schema::hasColumn('users_api_customers_stages', 'user_id') || !Schema::hasColumn('users_api_customers_stages', 'order')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'users_api_customers_stages' AND index_name = 'users_api_customers_stages_user_order_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) > 0) {
            return;
        }

        Schema::table('users_api_customers_stages', function (Blueprint $table) {
            $table->index(['user_id', 'order'], 'users_api_customers_stages_user_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users_api_customers_stages')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'users_api_customers_stages' AND index_name = 'users_api_customers_stages_user_order_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) === 0) {
            return;
        }

        Schema::table('users_api_customers_stages', function (Blueprint $table) {
            $table->dropIndex('users_api_customers_stages_user_order_index');
        });
    }
};
