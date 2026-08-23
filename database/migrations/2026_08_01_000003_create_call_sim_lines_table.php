<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_sim_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('trunk_id')->constrained('call_trunks')->onDelete('cascade');
            $table->string('label');
            $table->string('msisdn');                    // E.164 caller ID, e.g. +966512345678
            $table->string('asterisk_endpoint')->unique(); // exact PJSIP endpoint id
            $table->tinyInteger('port_index')->unsigned()->nullable(); // 1-8 for Yeastar GSM ports
            $table->unsignedBigInteger('user_id')->nullable(); // optional dedicated agent
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_sim_lines');
    }
};
