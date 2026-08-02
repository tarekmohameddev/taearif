<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_conversation_ai_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->unique()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('summary_through_message_id')->nullable();
            $table->text('situation')->nullable();
            $table->text('requirements')->nullable();
            $table->text('commitments')->nullable();
            $table->text('objections')->nullable();
            $table->string('tone', 30)->nullable();
            $table->json('facts')->nullable();
            $table->timestamp('bot_paused_until')->nullable();
            $table->string('handoff_reason', 100)->nullable();
            $table->timestamp('last_bot_reply_at')->nullable();
            $table->unsignedInteger('tokens_in_total')->default(0);
            $table->unsignedInteger('tokens_out_total')->default(0);
            $table->string('opt_out_status', 20)->nullable();
            $table->boolean('disclosed_as_assistant')->default(false);
            $table->timestamps();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversation_ai_states');
    }
};
