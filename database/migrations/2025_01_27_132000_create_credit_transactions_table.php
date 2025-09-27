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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Multi-tenant key
            $table->unsignedBigInteger('credit_package_id')->nullable()->index(); // Package purchased
            $table->string('transaction_type'); // 'purchase', 'usage', 'refund', 'admin_add', 'admin_remove'
            $table->integer('credits_amount'); // Positive for additions, negative for usage
            $table->decimal('amount_paid', 10, 2)->nullable(); // Amount paid in SAR (for purchases)
            $table->string('currency', 3)->default('SAR'); // Currency code
            $table->string('payment_method')->nullable(); // Payment gateway used
            $table->string('payment_transaction_id')->nullable(); // External payment transaction ID
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('reference_number')->nullable(); // Internal reference number
            $table->text('description')->nullable(); // Transaction description
            $table->json('metadata')->nullable(); // Additional transaction data (JSON)
            $table->unsignedBigInteger('created_by')->nullable(); // Admin who created (for admin transactions)
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'transaction_type']);
            $table->index(['user_id', 'status']);
            $table->index(['payment_transaction_id']);
            $table->index(['created_at']);
            $table->index(['reference_number']);
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('credit_package_id')->references('id')->on('credit_packages')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_transactions');
    }
};
