<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_media')) {
            Schema::create('tenant_media', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('disk')->default('public');
                $table->string('path');
                $table->string('url');
                $table->string('mime')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_media');
    }
};


