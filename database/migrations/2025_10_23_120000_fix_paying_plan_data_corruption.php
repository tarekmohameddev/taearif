<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes corrupted paying_plan data where building names
     * or other invalid values were stored instead of valid payment frequencies.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Log all corrupted paying_plan values before fixing
        $corruptedRecords = DB::table('rm_rentals')
            ->whereNotIn('paying_plan', [
                'monthly',
                'quarterly',
                'semi_annual',
                'annual',
                'custom',
                'one_time'
            ])
            ->whereNotNull('paying_plan')
            ->where('paying_plan', '!=', '')
            ->get();

        if ($corruptedRecords->isNotEmpty()) {
            \Log::info('Paying plan data corruption detected', [
                'total_corrupted' => $corruptedRecords->count(),
                'sample_records' => $corruptedRecords->take(10)->pluck('id', 'paying_plan')->toArray()
            ]);
        }

        // Step 2: Update corrupted records to 'monthly' as default
        // since most rentals are monthly and we can't determine the original intent
        DB::table('rm_rentals')
            ->whereNotIn('paying_plan', [
                'monthly',
                'quarterly',
                'semi_annual',
                'annual',
                'custom',
                'one_time'
            ])
            ->whereNotNull('paying_plan')
            ->where('paying_plan', '!=', '')
            ->update([
                'paying_plan' => 'monthly',
                'updated_at' => now()
            ]);

        // Step 3: Set NULL values to 'monthly' as well
        DB::table('rm_rentals')
            ->whereNull('paying_plan')
            ->orWhere('paying_plan', '')
            ->update([
                'paying_plan' => 'monthly',
                'updated_at' => now()
            ]);

        \Log::info('Paying plan data corruption fixed', [
            'records_updated' => $corruptedRecords->count(),
            'default_value_set' => 'monthly'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * Note: This migration cannot be fully reversed as we don't store
     * the original corrupted values. Manual data restoration required.
     *
     * @return void
     */
    public function down()
    {
        \Log::warning('Attempted to rollback paying_plan fix migration. Manual data restoration may be required.');
        // Cannot reverse this migration as original corrupted data is not stored
    }
};

