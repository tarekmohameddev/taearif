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

        if (!Schema::hasTable('api_customers')) {
            return;
        }

        // Composite for whereExists(api_customers.property_request_id, user_id, responsible_employee_id) in property-requests index
        if (!$hasIndex('api_customers', 'api_customers_pr_user_employee_index')) {
            $cols = ['property_request_id', 'user_id', 'responsible_employee_id'];
            if (Schema::hasColumns('api_customers', $cols)) {
                Schema::table('api_customers', function (Blueprint $table) {
                    $table->index(
                        ['property_request_id', 'user_id', 'responsible_employee_id'],
                        'api_customers_pr_user_employee_index'
                    );
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

        if (!Schema::hasTable('api_customers')) {
            return;
        }

        if ($hasIndex('api_customers', 'api_customers_pr_user_employee_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->dropIndex('api_customers_pr_user_employee_index');
            });
        }
    }
};
