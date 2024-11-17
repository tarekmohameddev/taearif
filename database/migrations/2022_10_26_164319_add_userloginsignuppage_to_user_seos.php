<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserloginsignuppageToUserSeos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_seos', function (Blueprint $table) {
            $table->string('meta_description_signup')->nullable();
            $table->string('meta_keyword_signup')->nullable();

            $table->string('meta_description_login')->nullable();
            $table->string('meta_keyword_login')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_seos', function (Blueprint $table) {
            //
        });
    }
}
