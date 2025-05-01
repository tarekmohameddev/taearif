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
        Schema::create('user_property_characteristics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('user_properties')->onDelete('cascade');
            $table->foreignId('facade_id')->nullable()->constrained('user_facades')->onDelete('set null');
            $table->float('length')->nullable();
            $table->float('width')->nullable();
            $table->float('street_width_north')->nullable();
            $table->float('street_width_south')->nullable();
            $table->float('street_width_east')->nullable();
            $table->float('street_width_west')->nullable();
            $table->integer('building_age')->nullable();
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
        Schema::dropIfExists('user_property_characteristics');
    }
};
