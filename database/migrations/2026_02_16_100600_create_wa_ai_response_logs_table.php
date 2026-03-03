<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_ai_response_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wa_number_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('message_id');
            $table->string('scenario', 50)->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('handed_off')->default(false);
            $table->string('language', 10)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wa_number_id')->references('id')->on('wa_numbers')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->index(['user_id', 'wa_number_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_ai_response_logs');
    }
};
