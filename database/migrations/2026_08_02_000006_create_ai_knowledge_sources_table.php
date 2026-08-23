<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 20);
            $table->string('name', 255);
            $table->string('file_path', 500)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('chunk_count')->default(0);
            $table->string('embedding_model', 100)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_indexed_at')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_sources');
    }
};
