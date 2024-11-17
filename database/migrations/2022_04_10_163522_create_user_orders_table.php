<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('billing_fname')->nullable();
            $table->string('billing_lname')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_number')->nullable();
            $table->string('shpping_country')->nullable();
            $table->string('shpping_fname')->nullable();
            $table->string('shpping_lname')->nullable();
            $table->string('shpping_address')->nullable();
            $table->string('shpping_city')->nullable();
            $table->string('shpping_email')->nullable();
            $table->string('shpping_number')->nullable();
            $table->decimal('cart_total')->default(0.00);
            $table->decimal('discount')->default(0.00);
            $table->decimal('tax')->default(0.00);
            $table->decimal('total')->default(0.00);
            $table->string('method')->nullable();
            $table->string('gateway_type')->nullable();
            $table->string('currency_code')->nullable();
            $table->string('order_number')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_charge')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('order_status')->nullable();
            $table->string('txnid')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('receipt')->nullable();
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
        Schema::dropIfExists('user_orders');
    }
}
