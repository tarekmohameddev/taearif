<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_crm_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('request_id');
            $table->enum('relation_type', ['ai_matched', 'manually_added', 'sent_to_customer']);
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('user_properties')->cascadeOnDelete();
            $table->foreign('request_id')->references('id')->on('crm_requests')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('api_customers')->nullOnDelete();
            $table->unique(['property_id', 'request_id', 'relation_type'], 'property_crm_relations_unique');
            $table->index(['property_id', 'relation_type']);
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_crm_relations');
    }
};
