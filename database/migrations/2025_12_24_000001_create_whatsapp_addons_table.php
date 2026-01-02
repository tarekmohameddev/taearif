<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_addons')) {
            return;
        }

        Schema::create('whatsapp_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_user_id')->constrained('whatsapp_users');
            $table->unsignedInteger('qty');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('payment_ref')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_addons');
    }
};

