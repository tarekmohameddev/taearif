<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds index (tenant_id, account_type, active) on users to optimize the
     * employees query: WHERE tenant_id = ? AND account_type = 'employee' AND active = 1.
     * Optional: helps when the employees list in property-requests filters is slow.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
        if (!Schema::hasColumn('users', 'tenant_id') || !Schema::hasColumn('users', 'account_type') || !Schema::hasColumn('users', 'active')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'users' AND index_name = 'users_tenant_account_active_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) > 0) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->index(['tenant_id', 'account_type', 'active'], 'users_tenant_account_active_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $result = DB::select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = 'users' AND index_name = 'users_tenant_account_active_index'",
            [$databaseName]
        );
        if ((int) ($result[0]->count ?? 0) === 0) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_tenant_account_active_index');
        });
    }
};
