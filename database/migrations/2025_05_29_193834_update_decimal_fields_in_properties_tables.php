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
        Schema::table('properties_tables', function (Blueprint $table) {

            Schema::table('user_properties', function (Blueprint $table) {
                $table->decimal('area', 15, 2)->change(); // 15 digits, 2 decimals
            });

            Schema::table('user_property_characteristics', function (Blueprint $table) {
                $table->decimal('length', 15, 2)->nullable()->change();
                $table->decimal('width', 15, 2)->nullable()->change();
                $table->decimal('street_width_north', 15, 2)->nullable()->change();
                $table->decimal('street_width_south', 15, 2)->nullable()->change();
                $table->decimal('street_width_east', 15, 2)->nullable()->change();
                $table->decimal('street_width_west', 15, 2)->nullable()->change();
                $table->decimal('size', 15, 2)->nullable()->change();
            });

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('properties_tables', function (Blueprint $table) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->integer('area')->change();
            });

            Schema::table('user_property_characteristics', function (Blueprint $table) {
                $table->integer('length')->nullable()->change();
                $table->integer('width')->nullable()->change();
                $table->integer('street_width_north')->nullable()->change();
                $table->integer('street_width_south')->nullable()->change();
                $table->integer('street_width_east')->nullable()->change();
                $table->integer('street_width_west')->nullable()->change();
                $table->integer('size')->nullable()->change();
            });

        });
    }
};
