<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_request_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('property_request_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title', 255);
            $table->string('type', 50)->comment('site_visit, office_meeting, phone_call, video_call, contract_signing, other');
            $table->dateTime('datetime');
            $table->unsignedInteger('duration')->default(30)->comment('minutes');
            $table->string('status', 20)->default('scheduled')->comment('scheduled, completed, cancelled, no_show');
            $table->tinyInteger('priority')->default(2)->comment('1=low, 2=medium, 3=high, 4=urgent');
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
        Schema::dropIfExists('property_request_appointments');
    }
};
