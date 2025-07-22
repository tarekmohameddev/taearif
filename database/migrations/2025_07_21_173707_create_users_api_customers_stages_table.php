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
        Schema::create('users_api_customers_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('users_api_customers_stages');
    }
};
