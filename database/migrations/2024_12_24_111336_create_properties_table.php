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
        Schema::create('user_properties', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('featured_image')->nullable();
            $table->string('floor_planning_image')->nullable();
            $table->string('video_image')->nullable();
            $table->float('price', 10, 2)->nullable();
            $table->string('purpose');
            $table->string('type')->comment('residential,commercial');
            $table->integer('beds')->nullable();
            $table->integer('bath')->nullable();
            $table->integer('area');
            $table->string('video_url')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('featured')->default(0);
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
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
        Schema::dropIfExists('user_properties');
    }
};
