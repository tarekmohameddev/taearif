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
        Schema::table('user_hero_statics', function (Blueprint $table) {
            $table->string('secound_btn_name')->nullable();
            $table->string('secound_btn_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hero_statics', function (Blueprint $table) {
            $table->dropColumn('secound_btn_name');
            $table->dropColumn('secound_btn_url');
        });
    }
};
