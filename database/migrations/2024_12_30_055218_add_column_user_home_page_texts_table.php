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
        Schema::table('user_home_page_texts', function (Blueprint $table) {

            $table->tinyText('about_image_two')->after('about_image')->nullable();
            $table->tinyText('why_choose_us_section_image_two')->after('why_choose_us_section_image')->nullable();
            $table->integer('years_of_expricence')->nullable();
            $table->string('featured_property_title')->nullable();
            $table->string('property_title')->nullable();
            $table->string('city_title')->nullable();
            $table->text('city_subtitle')->nullable();
            $table->string('project_title')->nullable();
            $table->text('project_subtitle')->nullable();
            $table->text('testimonial_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_home_page_texts', function (Blueprint $table) {
            $table->dropColumn('about_image_two', 'why_choose_us_section_image_two', 'years_of_expricence', 'featured_property_title', 'property_title', 'city_title', 'city_subtitle',  'project_title', 'project_subtitle', 'testimonial_text');
        });
    }
};
