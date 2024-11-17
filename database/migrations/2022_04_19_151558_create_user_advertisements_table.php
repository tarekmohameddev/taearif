<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_advertisements', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('ad_type')->nullable();
            $table->smallInteger('resolution_type')->comment('1 => 300 x 250, 2 => 300 x 600, 3 => 728 x 90	');
            $table->string('image')->nullable();
            $table->string('url')->nullable();
            $table->string('ad_slot')->nullable();
            $table->integer('views')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_advertisements');
    }
}
