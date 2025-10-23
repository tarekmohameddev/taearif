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
     * Adds a CHECK constraint to enforce valid paying_plan values
     *
     * @return void
     */
    public function up()
    {
        // Note: This should run AFTER the fix_paying_plan_data_corruption migration
        // to ensure all data is cleaned up first

        // For MySQL 8.0.16+, we can use CHECK constraints
        // For older versions, this will be ignored but won't cause errors

        DB::statement("
            ALTER TABLE rm_rentals
            ADD CONSTRAINT chk_paying_plan
            CHECK (paying_plan IN ('monthly', 'quarterly', 'semi_annual', 'annual', 'custom', 'one_time'))
        ");

        \Log::info('Added CHECK constraint for paying_plan field in rm_rentals table');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE rm_rentals DROP CONSTRAINT IF EXISTS chk_paying_plan");
        \Log::info('Removed CHECK constraint for paying_plan field from rm_rentals table');
    }
};

