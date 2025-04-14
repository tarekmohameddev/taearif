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
        Schema::create('api_user_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ارض- فيلا
            $table->string('slug')->unique();
            $table->enum('type', ['property', 'project']);
            $table->string('is_active')->default(1);
            $table->string('icon')->nullable();
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
        Schema::dropIfExists('api_user_categories');
    }
};
