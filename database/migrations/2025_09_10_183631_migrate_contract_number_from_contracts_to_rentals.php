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
     * @return void
     */
    public function up()
    {
        // First, ensure the target column exists in rm_rentals
        if (!Schema::hasColumn('rm_rentals', 'contract_number')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->string('contract_number', 255)->nullable()->after('office_fee');
            });
        }

        // Migrate data from contracts to rentals (latest contract per rental)
        DB::statement("
            UPDATE rm_rentals r
            INNER JOIN (
                SELECT
                    rental_id,
                    contract_number,
                    ROW_NUMBER() OVER (PARTITION BY rental_id ORDER BY created_at DESC) as rn
                FROM rm_contracts
                WHERE contract_number IS NOT NULL
            ) c ON r.id = c.rental_id AND c.rn = 1
            SET
                r.contract_number = c.contract_number
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Clear the contract_number field in rentals
        DB::statement("UPDATE rm_rentals SET contract_number = NULL");
    }
};