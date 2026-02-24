<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('marketing_channel_messages')) {
            return;
        }
        Schema::create('marketing_channel_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('channel_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();

            // Message details
            $table->string('recipient_phone', 50);
            $table->string('recipient_name')->nullable();
            $table->text('message_content');
            $table->enum('message_type', ['text', 'template', 'media'])->default('text');

            // Status tracking (Meta statuses: sent, delivered, read, failed)
            $table->string('status', 30)->default('pending');
            $table->string('provider_message_id', 191)->nullable()->index();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            // Timestamps for status changes
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            // Additional data
            $table->integer('credits_used')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('marketing_channels')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('api_customers')->nullOnDelete();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['channel_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->unique(['channel_id', 'provider_message_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketing_channel_messages');
    }
};
