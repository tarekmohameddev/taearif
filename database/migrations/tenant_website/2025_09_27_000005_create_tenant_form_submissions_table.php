<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('tenant_form_submissions')) {
            Schema::create('tenant_form_submissions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('form_type');
                $table->json('data');
                $table->timestamp('submitted_at');
                $table->string('ip')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tenant_form_submissions');
    }
};


