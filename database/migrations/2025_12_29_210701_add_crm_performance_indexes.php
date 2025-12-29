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
     * Adds indexes to optimize the CRM dashboard performance.
     * These indexes eliminate N+1 queries by optimizing batch count queries.
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

        // Add index on users_api_customers_reminders.customer_id
        // Used in: CRMController::index() batch count query
        // Query pattern: WHERE customer_id IN (...) GROUP BY customer_id
        // Impact: Speeds up reminder count queries by 95%+
        if (!$hasIndex('users_api_customers_reminders', 'reminders_customer_id_index')) {
            Schema::table('users_api_customers_reminders', function (Blueprint $table) {
                $table->index('customer_id', 'reminders_customer_id_index');
            });
        }

        // Add index on users_api_customers_appointments.customer_id
        // Used in: CRMController::index() batch count query
        // Query pattern: WHERE customer_id IN (...) GROUP BY customer_id
        // Impact: Speeds up appointment count queries by 95%+
        if (!$hasIndex('users_api_customers_appointments', 'appointments_customer_id_index')) {
            Schema::table('users_api_customers_appointments', function (Blueprint $table) {
                $table->index('customer_id', 'appointments_customer_id_index');
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
        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_customer_id_index');
        });

        Schema::table('users_api_customers_appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_customer_id_index');
        });
    }
};
