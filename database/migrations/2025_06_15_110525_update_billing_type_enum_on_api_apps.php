<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE api_apps
            MODIFY billing_type ENUM('free','one_time','subscription','paid','paid_trial')
                NOT NULL DEFAULT 'free'
        ");

        DB::table('api_apps')
            ->where('billing_type', 'one_time')
            ->update(['billing_type' => 'paid']);

        DB::table('api_apps')
            ->where('billing_type', 'subscription')
            ->where('trial_days', '>', 0)
            ->update(['billing_type' => 'paid_trial']);

        DB::table('api_apps')
            ->where('billing_type', 'subscription')
            ->where(function ($q) {
                $q->whereNull('trial_days')
                  ->orWhere('trial_days', '=', 0);
            })
            ->update(['billing_type' => 'paid']);


        DB::statement("
            ALTER TABLE api_apps
            MODIFY billing_type ENUM('free','paid','paid_trial')
                NOT NULL DEFAULT 'free'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE api_apps
            MODIFY billing_type ENUM('free','one_time','subscription','paid','paid_trial')
                NOT NULL DEFAULT 'free'
        ");

        DB::table('api_apps')
            ->where('billing_type', 'paid')
            ->update(['billing_type' => 'one_time']);

        DB::table('api_apps')
            ->where('billing_type', 'paid_trial')
            ->update(['billing_type' => 'subscription']);

        DB::statement("
            ALTER TABLE api_apps
            MODIFY billing_type ENUM('free','one_time','subscription')
                NOT NULL DEFAULT 'free'
        ");
    }
};
