<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_home_sections', function (Blueprint $table) {
            $table->tinyInteger('useful_links_section')->default(0);
            $table->tinyInteger('contact_us_section')->default(0);
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_home_sections', function (Blueprint $table) {
            $table->dropColumn(['useful_links_section', 'contact_us_section']);
        });
    }
};
