<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $connection->getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    public function up(): void
    {
        if ($this->indexExists('users_property_requests', 'users_property_requests_user_id_index')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->dropIndex('users_property_requests_user_id_index');
            });
        }

        if ($this->indexExists('users_property_requests', 'upr_neighborhood_id_idx')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->dropIndex('upr_neighborhood_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasColumn('users_property_requests', 'user_id') &&
            !$this->indexExists('users_property_requests', 'users_property_requests_user_id_index')
        ) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->index('user_id', 'users_property_requests_user_id_index');
            });
        }

        if (
            Schema::hasColumn('users_property_requests', 'districts_id') &&
            !$this->indexExists('users_property_requests', 'upr_neighborhood_id_idx')
        ) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->index('districts_id', 'upr_neighborhood_id_idx');
            });
        }
    }
};


