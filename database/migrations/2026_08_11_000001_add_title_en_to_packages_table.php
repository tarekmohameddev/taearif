<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('packages', 'title_en')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->string('title_en')->nullable()->after('title');
            });
        }

        $titles = [
            'الباقة المميزة سنوية' => 'Premium Annual Package',
            'الباقة الشهرية' => 'Monthly Package',
            'الباقة السنوية' => 'Annual Package',
            'الباقة المجانية' => 'Free Package',
        ];

        foreach ($titles as $title => $titleEn) {
            DB::table('packages')->where('title', $title)->update(['title_en' => $titleEn]);
        }

        Cache::forget('payment_active_packages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('packages', 'title_en')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('title_en');
            });
        }
    }
};
