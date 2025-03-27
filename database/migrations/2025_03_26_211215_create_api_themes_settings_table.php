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
        Schema::create('api_themes_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme_id')->unique();
            $table->string('name');
            $table->text('description');
            $table->string('thumbnail');
            $table->string('category');
            $table->boolean('active')->default(false);
            $table->boolean('popular')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_themes_settings');
    }
};
