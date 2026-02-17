<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type', 30); // image|video|document|audio
            $table->string('url', 500);
            $table->string('name', 255)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_message_attachments');
    }
};
