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
        Schema::create('user_lesson_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_id');
            $table->string('video_unique_name')->nullable();
            $table->string('video_original_name')->nullable();
            $table->time('video_duration')->nullable();
            $table->string('video_preview')->nullable();
            $table->string('file_unique_name')->nullable();
            $table->string('file_original_name')->nullable();
            $table->binary('text')->nullable();
            $table->text('code')->nullable();
            $table->string('type')->nullable();
            $table->integer('order_no')->default(1);
            $table->tinyInteger('completion_status')->default(0);
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
        Schema::dropIfExists('user_lesson_contents');
    }
};
