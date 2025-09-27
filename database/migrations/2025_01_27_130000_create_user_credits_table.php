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
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Multi-tenant key
            $table->integer('total_credits')->default(0); // Total credits available
            $table->integer('used_credits')->default(0); // Credits used this month
            $table->integer('monthly_limit')->default(5000); // Monthly credit limit
            $table->decimal('average_cost_per_credit', 8, 4)->default(0.05); // Average cost per credit in SAR
            $table->date('reset_date')->nullable(); // Date when monthly usage resets
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['reset_date']);
            
            // Foreign key constraint
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to ensure one credit record per user
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_credits');
    }
};
