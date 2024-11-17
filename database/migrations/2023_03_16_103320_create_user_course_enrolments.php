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
        Schema::create('user_course_enrolments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('course_id');
            $table->bigInteger('order_id');
            $table->string('billing_first_name');
            $table->string('billing_last_name');
            $table->string('billing_email');
            $table->string('billing_contact_number');
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_country');
            $table->decimal('course_price',8,2)->nullable();
            $table->decimal('discount',8,2)->nullable();
            $table->decimal('grand_total',8,2)->nullable();
            $table->string('currency_text')->nullable();
            $table->string('currency_text_position')->nullable();
            $table->string('currency_symbol')->nullable();
            $table->string('currency_symbol_position')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('gateway_type')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('attachment')->nullable();
            $table->string('invoice')->nullable();
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
        Schema::dropIfExists('user_course_enrolments');
    }
};
