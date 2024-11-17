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
        Schema::create('user_rooms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('slider_imgs');
            $table->string('featured_img');
            $table->tinyInteger('status');
            $table->smallInteger('bed');
            $table->smallInteger('bath');
            $table->integer('max_guests')->nullable();
            $table->decimal('rent', 8, 2);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->tinyInteger('is_featured')
                ->default(0)
                ->comment('0 means will not show in home page, 1 means will show in home page');
            $table->decimal('avg_rating', 8, 2)->nullable();
            $table->integer('quantity')->default(1);
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
        Schema::dropIfExists('user_rooms');
    }
};
