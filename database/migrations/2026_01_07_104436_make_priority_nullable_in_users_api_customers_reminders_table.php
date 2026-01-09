<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY priority TINYINT NULL COMMENT '1=low, 2=medium, 3=high'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY priority TINYINT NOT NULL DEFAULT 1 COMMENT '1=low, 2=medium, 3=high'
        ");
    }
};
