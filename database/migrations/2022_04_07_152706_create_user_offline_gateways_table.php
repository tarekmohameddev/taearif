<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOfflineGatewaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_offline_gateways', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->string('name')->nullable();
            $table->text('short_description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('serial_number')->default(0);
            $table->tinyInteger('is_receipt')->default(1);
            $table->integer('receipt')->nullable();
            $table->integer('item_checkout_status')->default(0);
            $table->integer('language_id')->default(0);
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
        Schema::dropIfExists('user_offline_gateways');
    }
}
