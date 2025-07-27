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
        Schema::create('users_api_customers_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('api_customers')->onDelete('cascade');
            $table->string('title');
            $table->string('type'); // e.g., meeting, call, follow-up
            $table->tinyInteger('priority')->default(1)->comment('1=low, 2=medium, 3=high');
            $table->text('note')->nullable();
            $table->dateTime('datetime');
            $table->integer('duration');
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
        Schema::dropIfExists('users_api_customers_appointments');
    }
};
