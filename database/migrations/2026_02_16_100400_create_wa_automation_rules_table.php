<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wa_number_id')->nullable();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('trigger', 50);
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('triggered_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wa_number_id')->references('id')->on('wa_numbers')->onDelete('set null');
            $table->foreign('template_id')->references('id')->on('wa_templates')->onDelete('set null');
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'trigger']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_automation_rules');
    }
};
