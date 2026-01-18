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
     * Adds composite index on memberships (package_id, expire_date) to optimize
     * PaymentController::index and similar queries: WHERE package_id IN (...)
     * AND expire_date >= ?
     *
     * @return void
     */
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

        if (Schema::hasTable('memberships') && !$hasIndex('memberships', 'idx_memberships_package_expire')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->index(['package_id', 'expire_date'], 'idx_memberships_package_expire');
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

        if (Schema::hasTable('memberships') && $hasIndex('memberships', 'idx_memberships_package_expire')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropIndex('idx_memberships_package_expire');
            });
        }
    }
};
