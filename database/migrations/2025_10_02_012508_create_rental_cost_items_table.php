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
        Schema::create('rental_cost_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Multi-tenant key
            $table->unsignedBigInteger('rental_id')->index();
            $table->string('name'); // Cost item name
            $table->decimal('cost', 12, 2); // Cost amount
            $table->enum('type', ['fixed', 'percentage']); // fixed amount or percentage
            $table->enum('payer', ['owner', 'tenant']); // Who pays: property owner or tenant
            $table->enum('payment_frequency', ['one_time', 'per_installment']); // When to pay
            $table->decimal('percentage_of', 12, 2)->nullable(); // If percentage, what is it based on (total_rental_amount or installment_amount)
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rental_id')->references('id')->on('rm_rentals')->onDelete('cascade');
            
            // Indexes
            $table->index(['rental_id', 'is_active']);
            $table->index(['user_id', 'payer']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_cost_items');
    }
};
