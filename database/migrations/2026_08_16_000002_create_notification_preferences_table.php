<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->boolean('enabled')->default(true);
            $table->boolean('sound')->default(true);
            $table->boolean('badge')->default(true);
            $table->boolean('popup')->default(true);
            $table->boolean('PROPERTY_REQUEST')->default(true);
            $table->boolean('CONTACT_MESSAGE')->default(true);
            $table->boolean('REMINDER')->default(true);
            $table->boolean('RENTAL')->default(true);
            $table->boolean('SYSTEM')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
