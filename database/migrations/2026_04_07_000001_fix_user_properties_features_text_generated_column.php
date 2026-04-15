<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_properties')) {
            return;
        }

        // features_text was previously a VARCHAR(255) VIRTUAL generated column and can break table rebuilds
        // when `features` JSON becomes large. Recreate it with safe truncation.
        if (Schema::hasColumn('user_properties', 'features_text')) {
            DB::statement('ALTER TABLE `user_properties` DROP COLUMN `features_text`');
        }

        DB::statement(
            "ALTER TABLE `user_properties`
             ADD COLUMN `features_text` VARCHAR(2048)
             GENERATED ALWAYS AS (
                LEFT(
                    COALESCE(CONVERT(JSON_UNQUOTE(JSON_EXTRACT(`features`, '$')) USING utf8mb4), ''),
                    2048
                )
             ) VIRTUAL"
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_properties')) {
            return;
        }

        if (Schema::hasColumn('user_properties', 'features_text')) {
            DB::statement('ALTER TABLE `user_properties` DROP COLUMN `features_text`');
        }

        // Restore original 255 size (still potentially risky if features JSON is large).
        DB::statement(
            "ALTER TABLE `user_properties`
             ADD COLUMN `features_text` VARCHAR(255)
             GENERATED ALWAYS AS (
                COALESCE(CONVERT(JSON_UNQUOTE(JSON_EXTRACT(`features`, '$')) USING utf8mb4), '')
             ) VIRTUAL"
        );
    }
};

