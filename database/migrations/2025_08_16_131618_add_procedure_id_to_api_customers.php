<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('api_customers', 'procedure_id')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->foreignId('procedure_id')->nullable()->after('stage_id');
            });
        }

        $hasFk = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'api_customers'
              AND COLUMN_NAME = 'procedure_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ");

        if (!$hasFk) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->foreign('procedure_id')
                    ->references('id')
                    ->on('users_api_customers_procedures')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('api_customers', 'procedure_id')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->dropForeign(['procedure_id']);
                $table->dropColumn('procedure_id');
            });
        }
    }
};
