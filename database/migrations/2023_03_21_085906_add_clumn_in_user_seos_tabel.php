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
        Schema::table('user_seos', function (Blueprint $table) {
            $table->string('meta_keyword_rooms')->nullable();
            $table->text('meta_description_rooms')->nullable();
            $table->string('meta_keyword_room_details')->nullable();
            $table->text('meta_description_room_details')->nullable();
            $table->string('meta_keyword_course')->nullable();
            $table->text('meta_description_course')->nullable();
            $table->string('meta_keyword_course_details')->nullable();
            $table->text('meta_description_course_details')->nullable();
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
};
