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
        Schema::create('user_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('thumbnail_image');
            $table->string('video_link')->nullable();
            $table->string('cover_image');
            $table->string('pricing_type');
            $table->decimal('previous_price', 8, 2)->nullable();
            $table->decimal('current_price', 8, 2)->nullable();
            $table->string('status')->default('draft');
            $table->string('is_featured')->default('no');
            $table->decimal('average_rating', 8, 2)->nullable();
            $table->time('duration')->nullable()->default('00:00:00');
            $table->tinyInteger('certificate_status')->default(1);
            $table->tinyInteger('video_watching')->default(1);
            $table->tinyInteger('quiz_completion')->default(0);
            $table->decimal('min_quiz_score', 8, 2)->default(0.00);
            $table->string('certificate_title')->nullable();
            $table->mediumText('certificate_text')->nullable();
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
        Schema::dropIfExists('user_courses');
    }
};
