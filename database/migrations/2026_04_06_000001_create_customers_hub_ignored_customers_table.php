<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers_hub_ignored_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_user_id')->comment('Owner/tenant user id (same as user_id in users_property_requests)');
            $table->string('phone_normalized', 30)->nullable()->comment('E.164-like normalized phone digits (output of PhoneNormalizer::normalize)');
            $table->unsignedBigInteger('customer_id')->nullable()->comment('Optional link to api_customers.id');
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('Employee or owner user id who added this entry');
            $table->timestamps();

            // Prevent duplicate entries for the same tenant+phone
            $table->unique(['tenant_user_id', 'phone_normalized'], 'chic_tenant_phone_unique');
            // Prevent duplicate entries for the same tenant+customer
            $table->unique(['tenant_user_id', 'customer_id'], 'chic_tenant_customer_unique');

            $table->index('tenant_user_id', 'chic_tenant_idx');
            $table->index(['tenant_user_id', 'phone_normalized'], 'chic_tenant_phone_idx');
            $table->index(['tenant_user_id', 'customer_id'], 'chic_tenant_customer_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_hub_ignored_customers');
    }
};
