<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

            return (int) ($result[0]->count ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        // Cover the common Customers Hub list filter for property requests:
        // WHERE user_id = ? AND is_active = 1 ORDER BY created_at/updated_at …
        // We already have upr_user_active_updated_idx; this complements it for created_at ordering.
        if (
            Schema::hasColumn('users_property_requests', 'user_id')
            && Schema::hasColumn('users_property_requests', 'is_active')
            && Schema::hasColumn('users_property_requests', 'created_at')
            && !$this->hasIndex('users_property_requests', 'upr_user_active_created_idx')
        ) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->index(['user_id', 'is_active', 'created_at'], 'upr_user_active_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            return;
        }

        if ($this->hasIndex('users_property_requests', 'upr_user_active_created_idx')) {
            Schema::table('users_property_requests', function (Blueprint $table) {
                $table->dropIndex('upr_user_active_created_idx');
            });
        }
    }
};

