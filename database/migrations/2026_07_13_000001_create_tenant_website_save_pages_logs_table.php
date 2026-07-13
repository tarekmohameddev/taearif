<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_website_save_pages_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('username')->nullable();
            $table->string('tenant_id_value')->nullable();
            $table->json('login_session_meta')->nullable();
            $table->string('server_ip')->nullable();
            $table->string('server_user_agent')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['tenant_id', 'created_at'], 'twspl_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_website_save_pages_logs');
    }
};
