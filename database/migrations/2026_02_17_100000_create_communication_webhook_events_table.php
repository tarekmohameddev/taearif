<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('communication_webhook_events')) {
            return;
        }

        Schema::create('communication_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel', 20);
            $table->string('provider', 50);
            $table->string('event_type', 30);
            $table->string('provider_event_id', 191)->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->string('event_hash', 64);
            $table->boolean('signature_valid')->default(false);
            $table->boolean('tenant_resolved')->default(false);
            $table->string('processing_result', 30)->default('processed');
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'comm_webhook_events_provider_event_id_unique');
            $table->unique(['provider', 'event_hash'], 'comm_webhook_events_provider_hash_unique');
            $table->index(['user_id', 'channel', 'received_at']);
            $table->index('provider_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_webhook_events');
    }
};
