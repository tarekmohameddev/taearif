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
            $table->tinyInteger('rooms_section')->default(1)->nullable();
            $table->tinyInteger('call_to_action_section_status')->default(1)->nullable();
            $table->tinyInteger('featured_courses_section_status')->default(1)->nullable();
            $table->tinyInteger('causes_section')->default(1)->nullable();
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
            $table->dropColumn('call_to_action_section_status');
            $table->dropColumn('featured_courses_section_status');
        });
    }
};
