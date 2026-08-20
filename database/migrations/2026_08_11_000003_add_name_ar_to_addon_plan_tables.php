<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('whatsapp_addon_plans', 'name_ar')) {
            Schema::table('whatsapp_addon_plans', function (Blueprint $table) {
                $table->string('name_ar')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('employee_addon_plans', 'name_ar')) {
            Schema::table('employee_addon_plans', function (Blueprint $table) {
                $table->string('name_ar')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_addon_plans', 'name_ar')) {
            Schema::table('whatsapp_addon_plans', function (Blueprint $table) {
                $table->dropColumn('name_ar');
            });
        }

        if (Schema::hasColumn('employee_addon_plans', 'name_ar')) {
            Schema::table('employee_addon_plans', function (Blueprint $table) {
                $table->dropColumn('name_ar');
            });
        }
    }
};
