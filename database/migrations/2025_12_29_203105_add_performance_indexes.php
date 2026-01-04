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

        // Add index on api_customers.user_id if it doesn't exist
        if (!$hasIndex('api_customers', 'api_customers_user_id_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index('user_id', 'api_customers_user_id_index');
            });
        }

        // Add index on users.tenant_id if it doesn't exist
        if (!$hasIndex('users', 'users_tenant_id_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('tenant_id', 'users_tenant_id_index');
            });
        }

        // Add composite index on users(tenant_id, account_type) for employee queries
        if (!$hasIndex('users', 'users_tenant_account_type_index')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index(['tenant_id', 'account_type'], 'users_tenant_account_type_index');
            });
        }

        // Add index on user_properties.user_id if it doesn't exist
        if (!$hasIndex('user_properties', 'user_properties_user_id_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index('user_id', 'user_properties_user_id_index');
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
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropIndex('api_customers_user_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_id_index');
            $table->dropIndex('users_tenant_account_type_index');
        });

        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex('user_properties_user_id_index');
        });
    }
};
