<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('property_request_appointments')
            ->where('status', 'scheduled')
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        DB::table('property_request_appointments')
            ->where('status', 'pending')
            ->update(['status' => 'scheduled']);
    }
};
