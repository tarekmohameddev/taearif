<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEcommerceToUserSeosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_seos', function (Blueprint $table) {
            $table->string('shop_meta_keywords')->nullable();
            $table->string('shop_meta_description')->nullable();

            $table->string('item_details_meta_keywords')->nullable();
            $table->string('item_details_meta_description')->nullable();

            $table->string('cart_meta_keywords')->nullable();
            $table->string('cart_meta_description')->nullable();

            $table->string('checkout_meta_keywords')->nullable();
            $table->string('checkout_meta_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_seos', function (Blueprint $table) {
            //
        });
    }
}
