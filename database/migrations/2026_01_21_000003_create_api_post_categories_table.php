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
        Schema::create('api_post_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('api_posts')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('api_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'category_id']);
            $table->index('post_id');
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_post_categories');
    }
};
