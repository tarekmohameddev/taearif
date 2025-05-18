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
        Schema::table('user_steps', function (Blueprint $table) {
            //
            $table->boolean('homepage_about_update')->default(false)->after('footer');
            $table->boolean('banner')->default(false)->after('homepage_about_update');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_steps', function (Blueprint $table) {
            //
            $table->dropColumn(['homepage_about_update', 'banner']);
        });
    }
};
