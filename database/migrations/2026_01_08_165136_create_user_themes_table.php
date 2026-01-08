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
        Schema::create('user_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('theme_id');
            $table->foreign('theme_id')->references('theme_id')->on('api_themes_settings')->onDelete('cascade');
            $table->timestamp('purchased_at');
            $table->enum('status', ['pending', 'active', 'rejected'])->default('pending');
            $table->string('payment_ref')->unique()->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->string('payment_method')->nullable(); // arb, myfatoorah, test
            $table->timestamps();

            // Ensure user can't purchase same theme twice
            $table->unique(['user_id', 'theme_id']);
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('theme_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_themes');
    }
};
