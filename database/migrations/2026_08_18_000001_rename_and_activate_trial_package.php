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
            'title' => 'الباقة التجريبية',
        ];

        if (Schema::hasColumn('packages', 'is_active')) {
            $payload['is_active'] = 1;
        }

        if (Schema::hasColumn('packages', 'title_en')) {
            $payload['title_en'] = 'Trial Package';
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
            'title' => 'الباقة المميزة مؤقتة',
        ];

        if (Schema::hasColumn('packages', 'is_active')) {
            $payload['is_active'] = 0;
        }

        if (Schema::hasColumn('packages', 'title_en')) {
            $payload['title_en'] = null;
        }

        DB::table('packages')
            ->where('id', self::PACKAGE_ID)
            ->where('title', 'الباقة التجريبية')
            ->update($payload);

        Cache::forget('payment_active_packages');
    }
};
