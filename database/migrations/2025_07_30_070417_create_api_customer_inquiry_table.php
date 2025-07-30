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
        Schema::create('api_customer_inquiry', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('customer_id');
            $table->text('message')->nullable();
            $table->string('inquiry_type')->nullable();
            $table->string('property_type')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            // Foreign key constraints (optional, remove if not needed)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_customer_inquiry');
    }
};
