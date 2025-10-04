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
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('building')->nullable()->after('project_id');
            $table->string('water_meter_number')->nullable()->after('building');
            $table->string('electricity_meter_number')->nullable()->after('water_meter_number');
            $table->string('deed_number')->nullable()->after('electricity_meter_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropColumn(['building', 'water_meter_number', 'electricity_meter_number', 'deed_number']);
        });
    }
};
