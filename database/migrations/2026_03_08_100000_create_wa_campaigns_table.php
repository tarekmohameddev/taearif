<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wa_campaigns')) {
            return;
        }

        Schema::create('wa_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('wa_number_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('dispatch_reference')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('reserved_credits')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('wa_number_id')->references('id')->on('wa_numbers')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('wa_templates')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'scheduled_at']);
            $table->index('template_id');
            $table->index('wa_number_id');
            $table->unique(['user_id', 'dispatch_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_campaigns');
    }
};
