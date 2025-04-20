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
        Schema::create('api_content_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('path')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->json('info')->nullable();
            $table->json('badge')->nullable();
            $table->string('lastUpdate')->nullable();
            $table->string('lastUpdateFormatted')->nullable();
            $table->integer('count')->nullable();
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
        Schema::dropIfExists('api_content_sections');
    }
};
