<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_global_components')) {
            Schema::create('tenant_global_components', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->json('data')->nullable();
                $table->json('published_data')->nullable();
                $table->timestamps();
                $table->unique('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_global_components');
    }
};


