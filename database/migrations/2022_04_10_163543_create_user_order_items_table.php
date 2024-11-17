<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserOrderItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_order_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('item_id')->nullable();
            $table->string('title')->nullable();
            $table->string('sku')->nullable();
            $table->integer('qty')->nullable();
            $table->string('category')->nullable();
            $table->string('image')->nullable();
            $table->text('summary')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price')->nullable();
            $table->decimal('previous_price')->nullable();
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
        Schema::dropIfExists('user_order_items');
    }
}
