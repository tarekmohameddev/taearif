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
            $table->string('featured_course_section_title')->nullable();
            $table->string('newsletter_image')->nullable();
            $table->text('newsletter_subtitle')->change()->nullable();
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
            $table->dropColumn('featured_course_section_title');
            $table->dropColumn('newsletter_image');
            $table->string('newsletter_subtitle')->change();
        });
    }
};
