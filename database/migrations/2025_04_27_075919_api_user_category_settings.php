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
        Schema::create('api_user_category_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->boolean('is_active')->default(true); // 1 for show, 0 for hide
            $table->timestamps();

            // Unique constraint to ensure one setting per user per category
            $table->unique(['user_id', 'category_id'], 'user_category_settings_user_id_category_id_unique');

            // Foreign keys with cascade on delete
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
            $table->foreign('category_id')
                  ->references('id')
                  ->on('api_user_categories')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_user_category_settings');
    }
};
