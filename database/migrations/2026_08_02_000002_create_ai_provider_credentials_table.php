<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider', 30);
            $table->string('base_url', 255)->nullable();
            $table->text('api_key_encrypted');
            $table->string('chat_model', 100);
            $table->string('fast_model', 100)->nullable();
            $table->string('embedding_model', 100)->nullable();
            $table->json('allowed_models')->nullable();
            $table->boolean('is_platform_default')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_credentials');
    }
};
