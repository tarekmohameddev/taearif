<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Copy api_customers.property_request_id into pivot, then drop column and its constraints/indexes.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('api_customers', 'property_request_id')) {
            return;
        }

        $now = now();
        $rows = DB::table('api_customers')
            ->whereNotNull('property_request_id')
            ->select('id as customer_id', 'property_request_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('api_customer_property_request')->insertOrIgnore([
                'customer_id' => $row->customer_id,
                'property_request_id' => $row->property_request_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('api_customers', function (Blueprint $table) {
            if ($this->indexExists('api_customers', 'api_customers_pr_user_employee_index')) {
                $table->dropIndex('api_customers_pr_user_employee_index');
            }
            $table->dropForeign(['property_request_id']);
            if ($this->indexExists('api_customers', 'api_customers_property_request_id_index')) {
                $table->dropIndex(['property_request_id']);
            }
            $table->dropColumn('property_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customers', 'property_request_id')) {
                $table->unsignedBigInteger('property_request_id')->nullable()->after('user_id');
            }
        });

        $pivotRows = DB::table('api_customer_property_request')->orderBy('id')->get();
        $perCustomer = $pivotRows->unique('customer_id');
        foreach ($perCustomer as $row) {
            DB::table('api_customers')
                ->where('id', $row->customer_id)
                ->update(['property_request_id' => $row->property_request_id]);
        }

        Schema::table('api_customers', function (Blueprint $table) {
            if (Schema::hasColumn('api_customers', 'property_request_id')) {
                $table->foreign('property_request_id')
                    ->references('id')
                    ->on('users_property_requests')
                    ->nullOnDelete();
                $table->index('property_request_id');
                if (!Schema::hasColumn('api_customers', 'responsible_employee_id')) {
                    return;
                }
                $table->index(
                    ['property_request_id', 'user_id', 'responsible_employee_id'],
                    'api_customers_pr_user_employee_index'
                );
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
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
    }
};
