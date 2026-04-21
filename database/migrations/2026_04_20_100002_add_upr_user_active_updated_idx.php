<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
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

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        // Covering index for the UNION arm ordering by updated_at
        // This allows MySQL to avoid a filesort inside the UNION arm before materialisation
        if (!$this->hasIndex('users_property_requests', 'upr_user_active_updated_idx')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->index(['user_id', 'is_active', 'updated_at'], 'upr_user_active_updated_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        if ($this->hasIndex('users_property_requests', 'upr_user_active_updated_idx')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->dropIndex('upr_user_active_updated_idx');
            });
        }
    }
};
