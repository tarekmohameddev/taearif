<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_static_pages') && ! Schema::hasColumn('tenant_static_pages', 'url')) {
            Schema::table('tenant_static_pages', function (Blueprint $table) {
                $table->string('url', 2048)->nullable()->after('components');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_static_pages') && Schema::hasColumn('tenant_static_pages', 'url')) {
            Schema::table('tenant_static_pages', function (Blueprint $table) {
                $table->dropColumn('url');
            });
        }
    }
};
