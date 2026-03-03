<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_ai_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wa_number_id');
            $table->boolean('enabled')->default(false);
            $table->boolean('business_hours_only')->default(false);
            $table->time('business_hours_start')->nullable();
            $table->time('business_hours_end')->nullable();
            $table->string('timezone', 50)->default('Asia/Riyadh');
            $table->json('scenarios');
            $table->string('tone', 20)->default('friendly');
            $table->string('language', 10)->default('ar');
            $table->text('custom_instructions')->nullable();
            $table->boolean('fallback_to_human')->default(true);
            $table->unsignedInteger('fallback_delay')->default(5);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wa_number_id')->references('id')->on('wa_numbers')->onDelete('cascade');
            $table->unique(['user_id', 'wa_number_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_ai_configs');
    }
};
