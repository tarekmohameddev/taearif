<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_communication_providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_type'); // 'whatsapp_meta', 'whatsapp_evolution', 'sms'
            $table->boolean('is_enabled')->default(false);
            $table->string('name')->nullable(); // Display name
            
            // Common fields
            $table->text('api_url')->nullable();
            $table->text('api_key')->nullable(); // Encrypted
            
            // WhatsApp Meta Cloud specific
            $table->string('phone_number_id')->nullable();
            $table->string('business_account_id')->nullable();
            $table->text('access_token')->nullable(); // Encrypted
            $table->text('webhook_verify_token')->nullable(); // Encrypted
            
            // WhatsApp Evolution specific
            $table->string('instance_name')->nullable();
            $table->text('evolution_api_key')->nullable(); // Encrypted
            
            // SMS specific
            $table->string('sms_provider')->nullable(); // 'twilio', 'unifonic', etc.
            $table->string('account_sid')->nullable();
            $table->string('from_number')->nullable();
            
            // Additional config (JSON)
            $table->json('config')->nullable();
            
            // Metadata
            $table->timestamp('last_tested_at')->nullable();
            $table->string('status')->default('unconfigured'); // unconfigured, configured, active, error
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('provider_type');
            $table->index('is_enabled');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_communication_providers');
    }
};
