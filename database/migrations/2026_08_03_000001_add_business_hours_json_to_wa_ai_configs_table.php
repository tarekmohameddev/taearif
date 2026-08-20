<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a `business_hours` JSON column that supersedes the three legacy scalar
 * columns (business_hours_only / business_hours_start / business_hours_end).
 *
 * The new column stores a per-day schedule, for example:
 * {
 *   "sunday":    {"open": true,  "from": "09:00", "to": "17:00"},
 *   "monday":    {"open": true,  "from": "09:00", "to": "17:00"},
 *   ...
 *   "saturday":  {"open": false}
 * }
 *
 * Existing rows that had the legacy columns set are migrated: when
 * business_hours_only was true the start/end values are spread across
 * all seven days, otherwise the column is left null (= always open).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->json('business_hours')->nullable()->after('business_hours_end');
        });

        // Migrate existing rows
        $rows = DB::table('wa_ai_configs')
            ->where('business_hours_only', true)
            ->whereNotNull('business_hours_start')
            ->whereNotNull('business_hours_end')
            ->get(['id', 'business_hours_start', 'business_hours_end']);

        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

        foreach ($rows as $row) {
            $schedule = [];
            foreach ($days as $day) {
                $schedule[$day] = [
                    'open' => true,
                    'from' => $row->business_hours_start ?? '09:00',
                    'to'   => $row->business_hours_end   ?? '17:00',
                ];
            }
            DB::table('wa_ai_configs')
                ->where('id', $row->id)
                ->update(['business_hours' => json_encode($schedule)]);
        }
    }

    public function down(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->dropColumn('business_hours');
        });
    }
};
