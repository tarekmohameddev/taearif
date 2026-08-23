<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_turn_traces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('tenant ID');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('trigger_message_id');
            $table->string('idempotency_key', 128)->unique()->comment('derived from provider message ID (wamid)');
            $table->json('brief_before')->nullable()->comment('CustomerBrief state before this turn');
            $table->json('brief_after')->nullable()->comment('CustomerBrief state after applying brief_updates');
            $table->json('steps')->nullable()->comment('full step trace: tool calls + final reply');
            $table->json('tool_call_log')->nullable()->comment('compact log of every tool call and result');
            $table->json('guard_violations')->nullable()->comment('CitationGuard violation messages');
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('decision', 20)->default('unknown')
                ->comment('delivered|shadow|handoff|skipped|failed');
            $table->text('rendered_reply')->nullable();
            $table->string('delivery_status', 20)->default('pending')
                ->comment('pending|sent|delivered|failed');
            $table->unsignedTinyInteger('delivery_attempts')->default(0);
            $table->string('cassette_key', 64)->nullable()
                ->comment('sha256 hash for deterministic replay matching');
            $table->timestamps();

            $table->index('user_id');
            $table->index('conversation_id');
            $table->index(['delivery_status', 'created_at'], 'idx_delivery_reconciler');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_turn_traces');
    }
};
