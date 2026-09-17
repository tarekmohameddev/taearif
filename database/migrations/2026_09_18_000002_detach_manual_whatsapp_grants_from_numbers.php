<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('whatsapp_addons')
            ->whereNotNull('user_id')
            ->where('gateway_transaction_id', 'manual_admin_grant')
            ->update(['whatsapp_number_id' => null]);
    }

    public function down(): void
    {
        // Tenant-scoped manual grants intentionally do not belong to a phone number.
    }
};
