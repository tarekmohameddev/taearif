<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $updated = DB::table('user_properties')
            ->whereNull('created_by')
            ->update(['created_by' => DB::raw('user_id')]);

        Log::info('Property created_by backfill completed', ['updated' => $updated]);
    }

    public function down(): void
    {
        // Non-reversible: cannot distinguish pre-backfill nulls from legitimately null rows.
    }
};
