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
            $table->string('about_snd_button_text')->nullable();
            $table->string('about_snd_button_url')->nullable();
            $table->string('skills_image')->nullable();
            $table->string('job_education_title')->nullable();
            $table->string('job_education_subtitle')->nullable();
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
            $table->dropColumn('about_snd_button_text');
            $table->dropColumn('about_snd_button_url');
            $table->dropColumn('skills_image');
            $table->dropColumn('job_education_title');
            $table->dropColumn('job_education_subtitle');
        });
    }
};
