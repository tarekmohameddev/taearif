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
        Schema::table('basic_extendeds', function (Blueprint $table) {
            $table->string('hero_section_subtitle')->nullable();
            $table->string('hero_section_secound_button_text')->nullable();
            $table->string('hero_section_secound_button_url')->nullable();
            $table->string('hero_img2')->nullable();
            $table->string('hero_img3')->nullable();
            $table->string('hero_img4')->nullable();
            $table->string('hero_img5')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_extendeds', function (Blueprint $table) {
            $table->dropColumn('hero_section_subtitle');
            $table->dropColumn('hero_section_secound_button_text');
            $table->dropColumn('hero_section_secound_button_url');
            $table->dropColumn('hero_img2');
            $table->dropColumn('hero_img3');
            $table->dropColumn('hero_img4');
            $table->dropColumn('hero_img5');
        });;
    }
};
