<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_items', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->default(0);
            $table->integer('stock')->default(0);
            $table->string('sku')->nullable();
            $table->string('thumbnail')->nullable();
            $table->decimal('current_price', 11, 2)->default(0);
            $table->decimal('previous_price', 11, 2)->default(0);
            $table->integer('is_feature')->default(0);
            $table->decimal('rating', 11, 2)->default(0.00);
            $table->string('type', 100)->comment('digital - digital product, physical - physical product')->nullable();
            $table->text('download_link')->nullable();
            $table->string('download_file', 100)->nullable();
            $table->integer('status')->default(1);
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
        Schema::dropIfExists('items');
    }
}
