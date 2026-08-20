<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_log_id');
            $table->foreign('call_log_id')->references('id')->on('call_logs')->onDelete('cascade');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('pending'); // pending | ready | failed
            $table->timestamps();

            $table->index(['call_log_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_recordings');
    }
};
