<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_message_logs')) {
            return;
        }

        Schema::create('email_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('recipient_email', 255);
            $table->string('recipient_name')->nullable();
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('gateway_message_id')->nullable();
            $table->string('provider')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('refund_processed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('email_campaigns')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('api_customers')->nullOnDelete();

            $table->unique(['user_id', 'gateway_message_id']);
            $table->index(['user_id', 'status']);
            $table->index(['campaign_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('gateway_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_message_logs');
    }
};

