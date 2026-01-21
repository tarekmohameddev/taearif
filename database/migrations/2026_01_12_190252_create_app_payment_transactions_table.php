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
        Schema::create('app_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('installation_id')->index();
            $table->unsignedBigInteger('app_id')->index();
            $table->string('payment_transaction_id')->unique(); // ARB PaymentID - unique for idempotency
            $table->string('gateway')->default('arb'); // Payment gateway: arb, myfatoorah, etc.
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending')->index();
            $table->json('gateway_response')->nullable(); // Full gateway response for audit
            $table->timestamp('verified_at')->nullable(); // When payment was verified
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['installation_id', 'status']);
            $table->index(['app_id', 'status']);
            $table->index(['created_at']);
            $table->index(['status', 'created_at']); // For background job queries

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('installation_id')->references('id')->on('api_installations')->onDelete('cascade');
            $table->foreign('app_id')->references('id')->on('api_apps')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_payment_transactions');
    }
};
