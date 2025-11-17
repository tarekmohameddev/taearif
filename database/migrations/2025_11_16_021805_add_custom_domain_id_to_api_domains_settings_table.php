<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1) Ensure the column exists (create as nullable INT, we’ll correct type below)
        Schema::table('api_domains_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('api_domains_settings', 'custom_domain_id')) {
                $table->integer('custom_domain_id')->nullable()->after('user_id');
            }
        });

        // 2) Detect actual type of user_custom_domains.id and align custom_domain_id
        $col = DB::selectOne("
            SELECT DATA_TYPE, COLUMN_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_custom_domains'
              AND COLUMN_NAME = 'id'
        ");

        if (!$col) {
            throw new \RuntimeException('Could not inspect user_custom_domains.id type');
        }

        // Normalize our child column to match parent (unsigned if parent is unsigned)
        $isBigInt   = stripos($col->COLUMN_TYPE, 'bigint') !== false;
        $isUnsigned = stripos($col->COLUMN_TYPE, 'unsigned') !== false;

        // Drop lingering FK if any (in case of previous failed attempts)
        try {
            DB::statement("ALTER TABLE `api_domains_settings` DROP FOREIGN KEY `api_domains_settings_custom_domain_id_foreign`");
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }

        if ($isBigInt) {
            // BIGINT [UNSIGNED]
            DB::statement("ALTER TABLE `api_domains_settings` MODIFY `custom_domain_id` BIGINT ".($isUnsigned ? 'UNSIGNED' : '')." NULL");
        } else {
            // INT [UNSIGNED]
            DB::statement("ALTER TABLE `api_domains_settings` MODIFY `custom_domain_id` INT ".($isUnsigned ? 'UNSIGNED' : '')." NULL");
        }

        // 3) Add FK with ON DELETE SET NULL
        Schema::table('api_domains_settings', function (Blueprint $table) {
            $table->foreign('custom_domain_id')
                ->references('id')
                ->on('user_custom_domains')
                ->nullOnDelete();
        });

        // 4) Backfill links
        DB::statement("
            UPDATE api_domains_settings ads
            JOIN user_custom_domains ucd
              ON ads.user_id = ucd.user_id
             AND (ads.custom_name = ucd.current_domain OR ads.custom_name = ucd.requested_domain)
            SET ads.custom_domain_id = ucd.id
            WHERE ads.custom_domain_id IS NULL
        ");
    }

    public function down()
    {
        Schema::table('api_domains_settings', function (Blueprint $table) {
            $table->dropForeign(['custom_domain_id']);
            $table->dropColumn('custom_domain_id');
        });
    }
};
