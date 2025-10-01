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
        Schema::create('marketing_channel_pricing', function (Blueprint $table) {
            $table->id();
            $table->enum('channel_type', ['whatsapp', 'facebook', 'telegram', 'instagram', 'sms'])->unique();
            $table->integer('credits_per_message'); // Credits required per message for this channel
            $table->decimal('price_per_credit', 10, 4); // Price per credit in SAR (calculated from credit packages)
            $table->decimal('effective_price_per_message', 10, 4); // Effective price per message (credits_per_message * price_per_credit)
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->json('channel_specific_settings')->nullable(); // Channel-specific settings
            $table->timestamps();

            // Indexes
            $table->index(['channel_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_channel_pricing');
    }
};