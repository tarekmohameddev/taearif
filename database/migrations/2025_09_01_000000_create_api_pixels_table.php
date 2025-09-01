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
        if (!Schema::hasTable('api_pixels')) {
            Schema::create('api_pixels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('platform', ['facebook', 'tiktok', 'snapchat']);
                $table->string('pixel_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Foreign key constraints
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                // Indexes for better performance
                $table->index('user_id');
                $table->index('platform');
                $table->index('is_active');
                
                // Unique constraint to ensure one pixel per platform per user
                $table->unique(['user_id', 'platform']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_pixels');
    }
}; 