<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $validUserIds = DB::table('users')->select('id');

        $skipped = DB::table('user_properties')
            ->whereNull('created_by')
            ->whereNotIn('user_id', $validUserIds)
            ->count();

        $updated = DB::table('user_properties')
            ->whereNull('created_by')
            ->whereIn('user_id', $validUserIds)
            ->update(['created_by' => DB::raw('user_id')]);

        Log::info('Property created_by backfill completed', [
            'updated' => $updated,
            'skipped_orphan_user_id' => $skipped,
        ]);
    }

    public function down(): void
    {
        // Non-reversible: cannot distinguish pre-backfill nulls from legitimately null rows.
    }
};
