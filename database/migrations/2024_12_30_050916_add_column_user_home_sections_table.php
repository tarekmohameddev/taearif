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
        Schema::table('user_home_sections', function (Blueprint $table) {
            $table->integer('featured_properties_section')->nullable()->default(1);
            $table->integer('property_section')->nullable()->default(1);
            $table->integer('project_section')->nullable()->default(1);
            $table->integer('cities_section')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_home_sections', function (Blueprint $table) {
            $table->dropColumn('featured_properties_section', 'property_section', 'project_section', 'cities_section');
        });
    }
};
