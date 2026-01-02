<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('whatsapp_user_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('customer_phone', 20)->index();
            $table->string('customer_name')->nullable();
            
            // Status tracking
            $table->enum('status', ['collecting', 'processed', 'archived'])->default('collecting')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->integer('message_count')->default(0);
            
            // AI Extraction fields
            $table->boolean('is_real_estate_inquiry')->default(false)->index();
            $table->string('inquiry_type', 20)->nullable(); // buy|rent|invest
            $table->string('property_type', 30)->nullable(); // apartment|villa|land|etc
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->tinyInteger('bedrooms')->unsigned()->nullable();
            $table->tinyInteger('bathrooms')->unsigned()->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('urgency', 20)->nullable(); // urgent|soon|flexible
            $table->boolean('furnished')->nullable();
            $table->text('ai_summary')->nullable();
            $table->json('extracted_data')->nullable();
            
            // Links to inquiry
            $table->unsignedBigInteger('inquiry_id')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('whatsapp_user_id')->references('id')->on('whatsapp_users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('set null');
            $table->foreign('inquiry_id')->references('id')->on('api_customer_inquiry')->onDelete('set null');
            
            // Unique constraint - one active conversation per whatsapp user + customer phone
            $table->unique(['whatsapp_user_id', 'customer_phone']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};

