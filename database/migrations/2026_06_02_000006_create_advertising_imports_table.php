<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertising_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->text('source_url');
            $table->enum('platform', ['aqar', 'deal', 'bayut', 'other']);
            $table->enum('status', ['pending', 'fetching', 'review', 'imported', 'failed'])->default('pending');
            $table->json('raw_data')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('user_properties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertising_imports');
    }
};
