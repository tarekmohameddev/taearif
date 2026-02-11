<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_request_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('property_request_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->dateTime('datetime');
            $table->tinyInteger('priority')->default(1)->comment('0=low, 1=medium, 2=high, 3=urgent');
            $table->string('type', 50)->comment('follow_up, payment_due, document_required, other');
            $table->string('status', 20)->default('pending')->comment('pending, completed, cancelled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_request_id')->references('id')->on('users_property_requests')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('set null');

            $table->index(['user_id', 'property_request_id']);
            $table->index(['property_request_id', 'datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_request_reminders');
    }
};
