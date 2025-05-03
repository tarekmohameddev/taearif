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
        Schema::table('user_property_characteristics', function (Blueprint $table) {
            $table->integer('rooms')->nullable()->after('building_age');
            $table->integer('bathrooms')->nullable();
            $table->integer('floors')->nullable();
            $table->integer('floor_number')->nullable();
            $table->integer('kitchen')->nullable();
            $table->integer('driver_room')->nullable();
            $table->integer('maid_room')->nullable();
            $table->integer('dining_room')->nullable();
            $table->integer('living_room')->nullable();
            $table->integer('majlis')->nullable();
            $table->integer('storage_room')->nullable();
            $table->integer('basement')->nullable();
            $table->integer('swimming_pool')->nullable();
            $table->integer('balcony')->nullable();
            $table->integer('garden')->nullable();
            $table->integer('annex')->nullable();
            $table->integer('elevator')->nullable();
            $table->integer('private_parking')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_property_characteristics', function (Blueprint $table) {
            $table->dropColumn([
                'rooms',
                'bathrooms',
                'floors',
                'floor_number',
                'driver_room',
                'maid_room',
                'dining_room',
                'living_room',
                'majlis',
                'storage_room',
                'basement',
                'swimming_pool',
                'kitchen',
                'balcony',
                'garden',
                'annex',
                'elevator',
                'private_parking',
            ]);
        });
    }
};
