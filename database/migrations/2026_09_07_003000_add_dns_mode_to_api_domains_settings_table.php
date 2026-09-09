<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_domains_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('api_domains_settings', 'dns_mode')) {
                $table->string('dns_mode', 32)
                    ->default('vercel_ns')
                    ->after('custom_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_domains_settings', function (Blueprint $table) {
            if (Schema::hasColumn('api_domains_settings', 'dns_mode')) {
                $table->dropColumn('dns_mode');
            }
        });
    }
};
