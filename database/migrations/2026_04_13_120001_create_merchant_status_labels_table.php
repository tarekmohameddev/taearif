<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchant_status_labels')) {
            return;
        }

        Schema::create('merchant_status_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('property_request_statuses')->cascadeOnDelete();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_status_labels');
    }
};
