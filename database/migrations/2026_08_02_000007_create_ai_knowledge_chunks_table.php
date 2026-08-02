<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->mediumText('content');
            $table->string('content_hash', 64)->index();
            $table->unsignedSmallInteger('chunk_index')->default(0);
            $table->mediumText('embedding_json');
            $table->string('embedding_model', 100);
            $table->unsignedSmallInteger('embedding_dims')->default(1536);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->foreign('source_id')->references('id')->on('ai_knowledge_sources')->onDelete('cascade');
            $table->index(['user_id', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_chunks');
    }
};
