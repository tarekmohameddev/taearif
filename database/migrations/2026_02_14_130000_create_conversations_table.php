<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('channel', 30)->default('whatsapp');
            $table->string('external_party_identifier', 191)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('external_party_identifier');
            $table->index('last_message_at');
            $table->unique(['user_id', 'channel', 'external_party_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
