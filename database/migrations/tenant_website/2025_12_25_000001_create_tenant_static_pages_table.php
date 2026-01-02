<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_static_pages')) {
            Schema::create('tenant_static_pages', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('page_id');
                $table->json('components');
                $table->json('published_data')->nullable();
                $table->timestamps();
                $table->unique(['user_id','page_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_static_pages');
    }
};

