<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_external_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('user_id');
            $table->string('platform', 60)->comment('e.g. aqar, bayut, property_finder, opensooq, custom');
            $table->string('url', 2048);
            $table->string('label', 120)->nullable()->comment('Optional display label');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('user_properties')->onDelete('cascade');
            $table->index(['property_id', 'active']);
            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_external_links');
    }
};
