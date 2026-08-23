<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            // UUID matches TAEARIF_CALL_ID injected into Asterisk channel variables
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            // Nullable: inbound calls may not match a customer immediately
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('api_customers')->onDelete('set null');
            // The agent who placed/received the call
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreignId('trunk_id')->nullable()->constrained('call_trunks')->onDelete('set null');
            $table->foreignId('sim_line_id')->nullable()->constrained('call_sim_lines')->onDelete('set null');
            $table->string('direction');    // outbound | inbound
            $table->string('to_e164');
            $table->string('from_e164')->nullable();
            $table->string('status');       // initiated|ringing_agent|ringing_dest|answered|completed|failed|busy|no_answer|canceled
            $table->string('fail_reason')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id', 'created_at']);
            $table->index(['tenant_id', 'user_id', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
