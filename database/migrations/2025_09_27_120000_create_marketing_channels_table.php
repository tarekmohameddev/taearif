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
        Schema::create('marketing_channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Multi-tenant key
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['whatsapp', 'facebook', 'telegram', 'instagram', 'sms']);
            $table->string('number', 50);
            $table->string('business_id', 100)->nullable();
            $table->string('phone_id', 100)->nullable();
            $table->text('access_token')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_connected')->default(false);
            $table->integer('sent_messages_count')->default(0);
            $table->integer('received_messages_count')->default(0);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_connected']);
            $table->index(['user_id', 'is_verified']);
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('marketing_channels');
    }
};
