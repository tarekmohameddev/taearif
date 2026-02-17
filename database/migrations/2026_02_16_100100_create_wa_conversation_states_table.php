<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_conversation_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wa_number_id')->nullable();
            $table->string('status', 20)->default('active'); // active|pending|resolved
            $table->boolean('is_starred')->default(false);
            $table->unsignedInteger('unread_count')->default(0);
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->string('last_message_preview', 500)->nullable();
            $table->timestamp('last_message_time')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('wa_number_id')->references('id')->on('wa_numbers')->onDelete('set null');
            $table->unique('conversation_id');
            $table->index(['user_id', 'status', 'last_message_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversation_states');
    }
};
