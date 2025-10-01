<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('marketing_channel_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Package name (e.g., "WhatsApp Basic Package")
            $table->string('name_ar')->nullable(); // Arabic name
            $table->text('description')->nullable(); // Package description
            $table->text('description_ar')->nullable(); // Arabic description
            $table->enum('channel_type', ['whatsapp', 'facebook', 'telegram', 'instagram', 'sms']); // Channel type
            $table->integer('credits_required'); // Credits required to use this package
            $table->decimal('price_per_message', 10, 4); // Price per message in SAR
            $table->string('currency', 3)->default('SAR'); // Currency code
            $table->integer('message_limit')->nullable(); // Message limit per package (null = unlimited)
            $table->integer('validity_days')->default(30); // Package validity in days
            $table->boolean('is_popular')->default(false); // Most popular package for this channel
            $table->boolean('is_active')->default(true); // Package availability
            $table->integer('sort_order')->default(0); // Display order
            $table->json('channel_specific_settings')->nullable(); // Channel-specific settings (JSON)
            $table->json('features')->nullable(); // Additional features (JSON array)
            $table->timestamps();

            // Indexes for performance
            $table->index(['channel_type', 'is_active', 'sort_order'], 'mcp_channel_active_sort_idx');
            $table->index(['is_popular'], 'mcp_popular_idx');
            $table->index(['channel_type', 'is_popular'], 'mcp_channel_popular_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_channel_packages');
    }
};