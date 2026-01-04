<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enforce one-to-one: each employee can only be linked to one WhatsApp number per tenant.
     */
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            // Unique index on (user_id, employee_id) where employee_id is not null
            // This ensures an employee can only be linked to one WhatsApp number per tenant
            $table->unique(['user_id', 'employee_id'], 'whatsapp_users_tenant_employee_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropUnique('whatsapp_users_tenant_employee_unique');
        });
    }
};

