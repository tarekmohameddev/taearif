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
        Schema::create('user_room_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('is_room')->default(1)->comment('1 = active, 0 = deactive');
            $table->tinyInteger('room_category_status')->default(1)->nullable()->comment('if is 1 active, 0 deactive');
            $table->tinyInteger('room_guest_checkout_status')->default(0)->nullable()->comment('if is 1 active, 0 deactive');
            $table->tinyInteger('room_rating_status')->default(0)->nullable()->comment('if is 1 active, 0 deactive');
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
        Schema::dropIfExists('user_room_settings');
    }
};
