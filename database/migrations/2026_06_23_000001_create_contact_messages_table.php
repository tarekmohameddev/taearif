<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->string('customer_phone', 40)->nullable();
            $table->text('message');
            $table->string('source', 50);
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('api_customers')->nullOnDelete();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'is_read', 'status']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
