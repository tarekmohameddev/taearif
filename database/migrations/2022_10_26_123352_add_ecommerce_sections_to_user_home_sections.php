<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEcommerceSectionsToUserHomeSections extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_home_sections', function (Blueprint $table) {
            $table->tinyInteger('category_section')->default(1)->nullable();
            $table->tinyInteger('slider_section')->default(1)->nullable();
            $table->tinyInteger('left_offer_banner_section')->default(1)->nullable();
            $table->tinyInteger('bottom_offer_banner_section')->default(1)->nullable();
            $table->tinyInteger('featured_item_section')->default(1)->nullable();
            $table->tinyInteger('new_item_section')->default(1)->nullable();
            $table->tinyInteger('toprated_item_section')->default(1)->nullable();
            $table->tinyInteger('bestseller_item_section')->default(1)->nullable();
            $table->tinyInteger('special_item_section')->default(1)->nullable();
            $table->tinyInteger('flashsale_item_section')->default(1)->nullable();
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
            //
        });
    }
}
