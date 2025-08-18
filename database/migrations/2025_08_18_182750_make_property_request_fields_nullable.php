<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


return new class extends Migration {
    public function up(): void
    {
        $table = 'users_property_requests';

        DB::statement("ALTER TABLE `$table`
            MODIFY `region` varchar(255) NULL");

        DB::statement("ALTER TABLE `$table`
            MODIFY `purchase_method` ENUM('كاش','تمويل بنكي') NULL");

        DB::statement("ALTER TABLE `$table`
            MODIFY `wants_similar_offers` TINYINT(1) NULL DEFAULT NULL");
    }

    public function down(): void
    {
        $table = 'users_property_requests';

        DB::statement("ALTER TABLE `$table`
            MODIFY `region` varchar(255) NOT NULL DEFAULT 'الرياض'");

        DB::statement("ALTER TABLE `$table`
            MODIFY `purchase_method` ENUM('كاش','تمويل بنكي') NOT NULL");

        DB::statement("ALTER TABLE `$table`
            MODIFY `wants_similar_offers` TINYINT(1) NOT NULL DEFAULT 0");
    }
};


