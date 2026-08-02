<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shadow_bot_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('trigger_message_id')->index();
            $table->text('draft_reply');
            $table->json('used_sources')->nullable();
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('agent_reply')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->timestamps();
            $table->index(['conversation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shadow_bot_drafts');
    }
};
