<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('mediable_id')->nullable();
            $table->string('mediable_type')->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('type'); // 'image' | 'video'
            $table->timestamps();
        });

        Schema::table('api_media', function (Blueprint $table) {
            $table->index(['mediable_type', 'mediable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_media');
    }
};
