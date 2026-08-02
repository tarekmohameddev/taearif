<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_unanswered_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->text('question');
            $table->string('cluster_key', 64)->nullable()->index();
            $table->unsignedSmallInteger('occurrence_count')->default(1);
            $table->boolean('added_to_faq')->default(false);
            $table->timestamps();
            $table->index(['user_id', 'added_to_faq']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_unanswered_questions');
    }
};
