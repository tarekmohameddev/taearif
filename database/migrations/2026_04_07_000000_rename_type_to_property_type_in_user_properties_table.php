<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If the table rebuild fails due to legacy oversized values in features_text (VARCHAR),
        // widen it to TEXT first so subsequent ALTER operations can succeed.
        if (Schema::hasTable('user_properties') && Schema::hasColumn('user_properties', 'features_text')) {
            try {
                DB::statement("ALTER TABLE `user_properties` MODIFY `features_text` TEXT NULL");
            } catch (\Throwable $e) {
                // ignore (column might already be TEXT or engine may not support MODIFY)
            }
        }

        // Rename `type` -> `property_type` without requiring doctrine/dbal.
        if (Schema::hasTable('user_properties') && Schema::hasColumn('user_properties', 'type') && ! Schema::hasColumn('user_properties', 'property_type')) {
            // Prefer MySQL 8+ metadata-only rename (avoids table rebuild).
            // Fallback to CHANGE for older MySQL versions.
            try {
                DB::statement("ALTER TABLE `user_properties` RENAME COLUMN `type` TO `property_type`");
            } catch (\Throwable $e) {
                // Assuming MySQL. `type` was created as string (VARCHAR 255).
                DB::statement("ALTER TABLE `user_properties` CHANGE `type` `property_type` VARCHAR(255) NULL");
            }
        }

        // Normalize any existing data to lowercase.
        if (Schema::hasTable('user_properties') && Schema::hasColumn('user_properties', 'property_type')) {
            DB::table('user_properties')
                ->whereNotNull('property_type')
                ->update([
                    'property_type' => DB::raw('LOWER(property_type)'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_properties') && Schema::hasColumn('user_properties', 'property_type') && ! Schema::hasColumn('user_properties', 'type')) {
            try {
                DB::statement("ALTER TABLE `user_properties` RENAME COLUMN `property_type` TO `type`");
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `user_properties` CHANGE `property_type` `type` VARCHAR(255) NULL");
            }
        }
    }
};

