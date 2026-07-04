<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_user_id');
            $table->string('type', 80);
            $table->string('title', 255);
            $table->text('body');
            $table->string('source_type', 50)->default('property_request');
            $table->unsignedBigInteger('source_id');
            $table->string('request_id', 100);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('dedupe_key', 191)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('tenant_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['tenant_user_id', 'source_type', 'source_id']);
            $table->index(['tenant_user_id', 'occurred_at']);
            $table->unique('dedupe_key');
        });

        Schema::create('app_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('recipient_user_id');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('notification_id')->references('id')->on('app_notifications')->onDelete('cascade');
            $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['notification_id', 'recipient_user_id'], 'app_notif_recipient_unique');
            $table->index(['recipient_user_id', 'read_at'], 'app_notif_recipient_unread_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notification_recipients');
        Schema::dropIfExists('app_notifications');
    }
};
