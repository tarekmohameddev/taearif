<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('settings')->default('{}');
                $table->string('version')->default('1');
                $table->timestamp('published_at')->nullable();
                $table->timestamps();
                $table->unique('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_settings');
    }
};


