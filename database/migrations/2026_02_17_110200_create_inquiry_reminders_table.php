<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reminders linked to customer inquiries (mirrors property_request_reminders).
     */
    public function up(): void
    {
        Schema::create('inquiry_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('inquiry_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('datetime');
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('snoozed_at')->nullable();
            $table->unsignedBigInteger('snoozed_by')->nullable();
            $table->tinyInteger('priority')->default(1)->comment('0=low, 1=medium, 2=high, 3=urgent');
            $table->string('type', 50)->comment('follow_up, payment_due, document_required, other');
            $table->string('status', 20)->default('pending')->comment('pending, completed, cancelled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('inquiry_id')->references('id')->on('api_customer_inquiry')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('set null');

            $table->index(['user_id', 'inquiry_id']);
            $table->index(['inquiry_id', 'datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_reminders');
    }
};
