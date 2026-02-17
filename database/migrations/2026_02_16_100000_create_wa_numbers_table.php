<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 30); // meta|evolution
            $table->string('phone_number', 20);
            $table->string('phone_number_id', 191)->nullable();
            $table->string('provider_account_id', 191)->nullable();
            $table->string('name', 100)->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedInteger('quota_limit')->default(5000);
            $table->unsignedInteger('quota_used')->default(0);
            $table->unsignedBigInteger('marketing_channel_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'phone_number']);
            $table->index(['user_id', 'status']);
            $table->index('marketing_channel_id');
            $table->index(['provider', 'phone_number_id']);
            $table->index(['provider', 'provider_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_numbers');
    }
};
