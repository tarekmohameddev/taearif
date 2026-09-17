<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_waba_reconciliation_logs')) {
            return;
        }

        Schema::create('whatsapp_waba_reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_user_id');
            $table->unsignedBigInteger('tenant_owner_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('phone_id', 191);
            $table->string('old_waba_id', 191)->nullable();
            $table->string('verified_waba_id', 191);
            $table->string('result', 32);
            $table->string('reason_code', 64);
            $table->string('subscription_result', 32)->nullable();
            $table->timestamps();

            $table->index(['whatsapp_user_id', 'created_at'], 'wa_waba_reconcile_number_created_idx');
            $table->index(['tenant_owner_id', 'created_at'], 'wa_waba_reconcile_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_waba_reconciliation_logs');
    }
};
