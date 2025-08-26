<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('card_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('card_id');
            $table->string('action', 50);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_type', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('changes')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'card_id']);
            $table->index(['tenant_id', 'action']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_logs');
    }
};
