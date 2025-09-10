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
        // First, ensure the office_fee column exists
        if (!Schema::hasColumn('rm_rentals', 'office_fee')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->decimal('office_fee', 12, 2)->default(0)->after('office_commission_value');
            });
        }

        // Calculate and populate office_fee for existing rentals
        DB::statement("
            UPDATE rm_rentals 
            SET office_fee = CASE 
                WHEN office_commission_type IS NULL 
                    OR office_commission_value IS NULL 
                    OR rental_period IS NULL 
                    OR base_rent_amount IS NULL 
                THEN 0
                WHEN office_commission_type = 'percentage' 
                THEN (rental_period * base_rent_amount) * (office_commission_value / 100)
                WHEN office_commission_type = 'amount' 
                THEN office_commission_value
                ELSE 0
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Clear the office_fee field
        DB::statement("UPDATE rm_rentals SET office_fee = 0");
    }
};