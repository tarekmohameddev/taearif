<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_agent_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // e.g. agent_42_7
            $table->string('sip_username')->unique();
            // stored encrypted; decrypted only when returning softphone-config to the owner
            $table->text('sip_password_encrypted');
            $table->string('extension')->nullable(); // optional short dial extension
            $table->string('asterisk_context');      // taearif-out
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One active extension per tenant+user pair
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_agent_extensions');
    }
};
