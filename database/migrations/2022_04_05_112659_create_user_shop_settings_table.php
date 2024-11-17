<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserShopSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_shop_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->tinyInteger('is_shop')->default(1);
            $table->tinyInteger('catalog_mode')->nullable()->default(0)->comment('1 - active, 0 - deactive');
            $table->tinyInteger('item_rating_system')->nullable()->default(1);
            $table->decimal('tax')->default(0.00);
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
        Schema::dropIfExists('user_shop_settings');
    }
}
