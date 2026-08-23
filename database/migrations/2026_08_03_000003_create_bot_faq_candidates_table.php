<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate FAQ entries produced by the LLM mining pass.
 *
 * Each row represents a recurring customer question cluster, with an LLM-drafted
 * answer. Per the operator decision, drafts are auto-promoted into ai_knowledge_sources
 * immediately (approval_status = 'auto_approved'). Tenants can later edit or delete
 * entries through the Phase 6 API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_faq_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('cluster_key', 64)->comment('SHA-256 of canonical question used for dedup');
            $table->text('question');
            $table->text('drafted_answer');
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->string('approval_status', 20)->default('auto_approved')
                ->comment('auto_approved | pending | rejected');
            $table->unsignedBigInteger('knowledge_source_id')->nullable()
                ->comment('ai_knowledge_sources row created when promoted');
            $table->string('mine_batch', 40)->nullable()
                ->comment('Identifier of the mining run that produced this candidate');
            $table->timestamps();

            $table->unique(['user_id', 'cluster_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_faq_candidates');
    }
};
