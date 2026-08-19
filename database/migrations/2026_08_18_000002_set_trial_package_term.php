<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PACKAGE_ID = 26;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        $payload = [
            'term' => 'trial',
        ];

        if (Schema::hasColumn('packages', 'is_trial')) {
            $payload['is_trial'] = 1;
        }

        if (Schema::hasColumn('packages', 'trial_days')) {
            $payload['trial_days'] = 7;
        }

        DB::table('packages')->where('id', self::PACKAGE_ID)->update($payload);

        Cache::forget('payment_active_packages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('packages')) {
            return;
        }

        $payload = [
            'term' => 'monthly',
        ];

        if (Schema::hasColumn('packages', 'is_trial')) {
            $payload['is_trial'] = 0;
        }

        if (Schema::hasColumn('packages', 'trial_days')) {
            $payload['trial_days'] = 0;
        }

        DB::table('packages')
            ->where('id', self::PACKAGE_ID)
            ->where('term', 'trial')
            ->update($payload);

        Cache::forget('payment_active_packages');
    }
};
