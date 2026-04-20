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
        if (!Schema::hasTable('customers_hub_stage_overrides')) {
            return;
        }

        // Index for the LEFT JOIN in enrichItemsWithHubStage
        if (!$this->hasIndex('customers_hub_stage_overrides', 'chso_user_stage_idx')) {
            Schema::table('customers_hub_stage_overrides', function (Blueprint $table) {
                $table->index(['user_id', 'stage_id'], 'chso_user_stage_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('customers_hub_stage_overrides')) {
            return;
        }

        if ($this->hasIndex('customers_hub_stage_overrides', 'chso_user_stage_idx')) {
            Schema::table('customers_hub_stage_overrides', function (Blueprint $table) {
                $table->dropIndex('chso_user_stage_idx');
            });
        }
    }
};
