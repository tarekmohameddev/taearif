<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_delivery_attempts')) {
            return;
        }

        Schema::create('communication_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('channel', 20);
            $table->string('provider', 50);
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('wa_number_id')->nullable();
            $table->unsignedSmallInteger('attempt_no');
            $table->string('attempt_status', 30);
            $table->boolean('retry_eligible')->default(false);
            $table->string('provider_message_id', 191)->nullable();
            $table->boolean('is_transient_failure')->default(false);
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('dispatched_at');
            $table->timestamp('completed_at')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'attempt_no'], 'comm_delivery_attempts_subject_attempt_unique');
            $table->index(['attempt_status', 'next_retry_at'], 'comm_delivery_attempts_status_retry_idx');
            $table->index(['user_id', 'channel', 'subject_type', 'subject_id'], 'comm_delivery_attempts_user_channel_idx');
            $table->index('provider_message_id', 'comm_delivery_attempts_provider_msg_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_delivery_attempts');
    }
};
