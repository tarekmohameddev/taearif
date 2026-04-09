<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers_hub_status_mapping')) {
            return;
        }

        // Expand enum to include in_waiting (MySQL/MariaDB).
        // Keeping the full list explicit avoids accidentally dropping existing values.
        DB::statement("
            ALTER TABLE customers_hub_status_mapping
            MODIFY customers_hub_status
            ENUM('pending','in_progress','in_waiting','completed','dismissed')
            NOT NULL
        ");

        // Update mapping for waiting -> in_waiting (if row exists).
        DB::table('customers_hub_status_mapping')
            ->where('property_request_status_slug', 'waiting')
            ->update([
                'customers_hub_status' => 'in_waiting',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers_hub_status_mapping')) {
            return;
        }

        // Revert enum (drops in_waiting).
        DB::statement("
            ALTER TABLE customers_hub_status_mapping
            MODIFY customers_hub_status
            ENUM('pending','in_progress','completed','dismissed')
            NOT NULL
        ");

        // Best-effort revert waiting mapping back to in_progress
        DB::table('customers_hub_status_mapping')
            ->where('property_request_status_slug', 'waiting')
            ->update([
                'customers_hub_status' => 'in_progress',
                'updated_at' => now(),
            ]);
    }
};

