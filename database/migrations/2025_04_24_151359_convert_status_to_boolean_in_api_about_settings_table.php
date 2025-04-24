<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        DB::statement("ALTER TABLE api_about_settings MODIFY status VARCHAR(5) NOT NULL DEFAULT 'off'");
        DB::table('api_about_settings')->where('status', 'on')->update(['status' => 1]);
        DB::table('api_about_settings')->where('status', 'off')->update(['status' => 0]);
        DB::statement("ALTER TABLE api_about_settings MODIFY status TINYINT(1) NOT NULL DEFAULT 0");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('api_about_settings')->where('status', 1)->update(['status' => 'on']);
        DB::table('api_about_settings')->where('status', 0)->update(['status' => 'off']);
        DB::statement("ALTER TABLE api_about_settings MODIFY status ENUM('on', 'off') NOT NULL DEFAULT 'off'");

    }
};
