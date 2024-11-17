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
            $table->string('category_section_title')->nullable();
            $table->string('category_section_subtitle')->nullable();
            $table->string('rooms_section_title')->nullable();
            $table->string('rooms_section_subtitle')->nullable();
            $table->text('rooms_section_content')->nullable();
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
            $table->dropColumn('category_section_title');
        });
    }
};
