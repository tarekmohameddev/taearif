<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->text('token');
            $table->enum('provider', ['fcm', 'apns']);
            $table->string('platform', 32);
            $table->string('device_id', 191);
            $table->string('app_id', 191)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('locale', 20)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('tenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'device_id'], 'push_tokens_user_device_unique');
            $table->index(['tenant_id', 'active'], 'push_tokens_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_push_tokens');
    }
};
