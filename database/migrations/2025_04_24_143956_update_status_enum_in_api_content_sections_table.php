<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE api_content_sections MODIFY status ENUM('on', 'off', 'active', 'inactive') NOT NULL DEFAULT 'on'");
        DB::table('api_content_sections')->where('status', 'active')->update(['status' => 'on']);
        DB::table('api_content_sections')->where('status', 'inactive')->update(['status' => 'off']);
        DB::statement("ALTER TABLE api_content_sections MODIFY status ENUM('on', 'off') NOT NULL DEFAULT 'on'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE api_content_sections MODIFY status ENUM('on', 'off', 'active', 'inactive') NOT NULL DEFAULT 'active'");
        DB::table('api_content_sections')->where('status', 'on')->update(['status' => 'active']);
        DB::table('api_content_sections')->where('status', 'off')->update(['status' => 'inactive']);
        DB::statement("ALTER TABLE api_content_sections MODIFY status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }
};
