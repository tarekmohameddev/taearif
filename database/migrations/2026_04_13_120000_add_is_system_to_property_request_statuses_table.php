<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        if (!Schema::hasColumn('property_request_statuses', 'is_system')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_active');
            });
        }

        DB::table('property_request_statuses')
            ->whereNull('user_id')
            ->whereIn('slug', ['suspended', 'completed', 'cancelled'])
            ->update(['is_system' => true]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        if (Schema::hasColumn('property_request_statuses', 'is_system')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
