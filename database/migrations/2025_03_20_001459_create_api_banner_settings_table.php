<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApiBannerSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('api_banner_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Banner type (static or slider)
            $table->string('banner_type')->default('static'); // static or slider
            
            // Static banner settings
            $table->json('static')->nullable();
            
            // Slider banner settings
            $table->json('slider')->nullable();
            
            // Common settings
            $table->json('common')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_banner_settings');
    }
}