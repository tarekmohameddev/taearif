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
        Schema::create('user_room_bookings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('booking_number');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->unsignedBigInteger('room_id');
            $table->date('arrival_date');
            $table->date('departure_date');
            $table->integer('guests');
            $table->double('subtotal', 8, 2)->nullable();
            $table->double('discount', 8, 2);
            $table->double('grand_total', 8, 2);
            $table->string('currency_symbol');
            $table->string('currency_symbol_position');
            $table->string('currency_text');
            $table->string('currency_text_position');
            $table->string('payment_method');
            $table->string('gateway_type');
            $table->string('attachment')->nullable();
            $table->string('invoice')->nullable();
            $table->tinyInteger('payment_status')->default(0)->comment('0 -> payment incomplete, 1 -> payment complete');
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
        Schema::dropIfExists('user_room_bookings');
    }
};
