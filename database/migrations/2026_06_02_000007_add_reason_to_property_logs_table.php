<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('property_logs', 'reason')) {
            Schema::table('property_logs', function (Blueprint $table) {
                $table->string('reason', 500)->nullable()->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('property_logs', 'reason')) {
            Schema::table('property_logs', function (Blueprint $table) {
                $table->dropColumn('reason');
            });
        }
    }
};
