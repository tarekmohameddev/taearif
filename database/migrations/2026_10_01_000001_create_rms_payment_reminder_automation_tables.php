<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rms_payment_reminder_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('channel_type', 16)->default('whatsapp');
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('sender_id')->nullable();
            $table->time('sending_time')->default('09:00:00');
            $table->string('timezone', 64)->default('Asia/Riyadh');
            $table->json('rules');
            $table->timestamps();
        });

        Schema::create('rms_payment_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rental_id')->nullable()->index();
            $table->unsignedBigInteger('installment_id')->nullable()->index();
            $table->string('rule_key', 80);
            $table->string('stage', 32);
            $table->string('channel_type', 16);
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('channel_name')->nullable();
            $table->string('recipient_phone', 32)->nullable();
            $table->string('sender_phone', 64)->nullable();
            $table->string('sender_id', 128)->nullable();
            $table->dateTime('scheduled_at');
            $table->dateTime('attempted_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 16)->default('pending');
            $table->string('skipped_reason')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->string('provider', 80)->nullable();
            $table->unsignedBigInteger('communication_message_id')->nullable()->index();
            $table->text('message_content');
            $table->decimal('outstanding_balance', 12, 2)->nullable();
            $table->date('local_day');
            $table->timestamps();
            $table->unique(['installment_id', 'channel_type', 'local_day'], 'rms_reminder_installment_channel_day_uq');
            $table->index(['user_id', 'scheduled_at', 'status'], 'rms_reminder_user_sched_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rms_payment_reminder_logs');
        Schema::dropIfExists('rms_payment_reminder_automations');
    }
};
