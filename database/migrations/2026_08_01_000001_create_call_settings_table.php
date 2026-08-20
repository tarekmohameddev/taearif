<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->boolean('record_by_default')->default(false);
            $table->boolean('play_recording_announcement')->default(false);
            $table->unsignedSmallInteger('max_channels')->default(5);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_settings');
    }
};
