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
        Schema::create('user_donation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('donation_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name')->nullable()->default('anonymous');
            $table->string('email')->nullable()->default('anonymous');
            $table->string('phone')->nullable()->default('xxxxxxxxxxxx');
            $table->decimal('amount', 11, 2)->default(0.00);
            $table->string('currency');
            $table->string('currency_position')->default('right');
            $table->string('currency_symbol');
            $table->string('currency_symbol_position')->default('left');
            $table->string('payment_method');
            $table->string('transaction_id');
            $table->string('status')->nullable();
            $table->string('invoice')->nullable();
            $table->longText('receipt')->nullable();
            $table->longText('transaction_details')->nullable();
            $table->longText('bex_details')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_donation_details');
    }
};
