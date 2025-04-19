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
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('featured_image')->nullable()->change();
            $table->text('floor_planning_image')->nullable()->change();
            $table->string('video_image')->nullable()->change();
            $table->decimal('price', 15, 2)->nullable()->change();
            $table->string('purpose')->nullable()->change();
            $table->string('type')->nullable()->change();
            $table->integer('beds')->nullable()->change();
            $table->integer('bath')->nullable()->change();
            $table->integer('area')->nullable()->change();
            $table->string('video_url')->nullable()->change();
            $table->integer('status')->nullable()->change();
            $table->boolean('featured')->nullable()->change();
            $table->text('features')->nullable()->change();
            $table->decimal('latitude', 10, 6)->nullable()->change();
            $table->decimal('longitude', 10, 6)->nullable()->change();
            $table->unsignedBigInteger('region_id')->nullable()->change();
            $table->unsignedBigInteger('category_id')->nullable()->change();

        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('featured_image')->nullable(false)->change();
            $table->text('floor_planning_image')->nullable(false)->change();
            $table->string('video_image')->nullable(false)->change();
            $table->decimal('price', 15, 2)->nullable(false)->change();
            $table->string('purpose')->nullable(false)->change();
            $table->string('type')->nullable(false)->change();
            $table->integer('beds')->nullable(false)->change();
            $table->integer('bath')->nullable(false)->change();
            $table->integer('area')->nullable(false)->change();
            $table->string('video_url')->nullable(false)->change();
            $table->integer('status')->nullable(false)->change();
            $table->boolean('featured')->nullable(false)->change();
            $table->text('features')->nullable(false)->change();
            $table->decimal('latitude', 10, 6)->nullable(false)->change();
            $table->decimal('longitude', 10, 6)->nullable(false)->change();
            $table->unsignedBigInteger('region_id')->nullable(false)->change();
            $table->unsignedBigInteger('category_id')->nullable(false)->change();

        });

    }
};
