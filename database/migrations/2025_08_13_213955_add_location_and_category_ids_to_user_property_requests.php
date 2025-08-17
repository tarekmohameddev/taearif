<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('property_type');
                $table->index('category_id', 'upr_category_id_idx');
            }
            if (!Schema::hasColumn('users_property_requests', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('category_id');
                $table->index('city_id', 'upr_city_id_idx');
            }
            if (!Schema::hasColumn('users_property_requests', 'neighborhood_id')) {
                $table->unsignedBigInteger('neighborhood_id')->nullable()->after('city_id');
                $table->index('neighborhood_id', 'upr_neighborhood_id_idx');
            }
        });

        DB::statement("ALTER TABLE `users_property_requests` MODIFY `property_type` VARCHAR(50) NULL");

        $possibleCols = ['name_ar','name_en','name','title_ar','title_en','title'];
        $joinParts = [];
        foreach ($possibleCols as $col) {
            if (Schema::hasColumn('api_user_categories', $col)) {
                // c.`col` = upr.property_type
                $joinParts[] = "c.`{$col}` = upr.`property_type`";
            }
        }

        if (!empty($joinParts)) {
            $onClause = implode(' OR ', $joinParts);
            DB::statement("
                UPDATE `users_property_requests` upr
                JOIN `api_user_categories` c
                  ON ($onClause)
                SET upr.`category_id` = c.`id`
                WHERE upr.`category_id` IS NULL
                  AND upr.`property_type` IS NOT NULL
                  AND upr.`property_type` <> ''
            ");
        }

        if (Schema::hasColumn('users_property_requests', 'category')) {
            DB::statement("
                UPDATE `users_property_requests`
                SET `property_type` = `category`
                WHERE `category` IN ('سكني','تجاري')
            ");
        }

        DB::statement("
            UPDATE `users_property_requests`
            SET `property_type` = 'سكني'
            WHERE `property_type` IS NULL OR `property_type` = ''
        ");

        DB::statement("
            ALTER TABLE `users_property_requests`
            MODIFY `property_type` ENUM('سكني','تجاري') NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `users_property_requests` MODIFY `property_type` VARCHAR(50) NULL");

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'neighborhood_id')) {
                $table->dropIndex('upr_neighborhood_id_idx');
            }
            if (Schema::hasColumn('users_property_requests', 'city_id')) {
                $table->dropIndex('upr_city_id_idx');
            }
            if (Schema::hasColumn('users_property_requests', 'category_id')) {
                $table->dropIndex('upr_category_id_idx');
            }

            $cols = [];
            if (Schema::hasColumn('users_property_requests', 'neighborhood_id')) $cols[] = 'neighborhood_id';
            if (Schema::hasColumn('users_property_requests', 'city_id')) $cols[] = 'city_id';
            if (Schema::hasColumn('users_property_requests', 'category_id')) $cols[] = 'category_id';
            if ($cols) $table->dropColumn($cols);
        });
    }
};
