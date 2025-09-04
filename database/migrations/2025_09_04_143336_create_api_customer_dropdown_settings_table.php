<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_customer_dropdown_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_login')->default(true);
            $table->boolean('show_register')->default(true);
            $table->boolean('show_dashboard')->default(true);
            $table->boolean('show_logout')->default(true);
            $table->json('additional_settings')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_customer_dropdown_settings');
    }
};
