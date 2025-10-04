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
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Building name (required)
            $table->string('image')->nullable(); // Building image
            $table->string('deed_number')->nullable(); // Deed number
            $table->string('deed_image')->nullable(); // Deed image
            $table->string('water_meter_number')->nullable(); // Water meter number
            $table->unsignedBigInteger('user_id'); // User who created the building
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('buildings');
    }
};
