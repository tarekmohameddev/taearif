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
        // First, ensure the rental table has the new columns
        if (!Schema::hasColumn('rm_rentals', 'platform_fee')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->decimal('platform_fee', 10, 2)->nullable()->after('deposit_amount');
            });
        }
        
        if (!Schema::hasColumn('rm_rentals', 'water_fee')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->decimal('water_fee', 10, 2)->nullable()->after('platform_fee');
            });
        }

        // Migrate platform_fee and water_fee_monthly from contracts to rentals
        // Take the latest contract's values for each rental
        DB::statement("
            UPDATE rm_rentals r
            INNER JOIN (
                SELECT 
                    rental_id,
                    platform_fee,
                    water_fee_monthly,
                    ROW_NUMBER() OVER (PARTITION BY rental_id ORDER BY created_at DESC) as rn
                FROM rm_contracts 
                WHERE platform_fee IS NOT NULL OR water_fee_monthly IS NOT NULL
            ) c ON r.id = c.rental_id AND c.rn = 1
            SET 
                r.platform_fee = c.platform_fee,
                r.water_fee = c.water_fee_monthly
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration cannot be easily reversed as we're moving data
        // The data would need to be manually restored if needed
        // For now, we'll just clear the fields in rentals
        DB::statement("
            UPDATE rm_rentals 
            SET platform_fee = NULL, water_fee = NULL
        ");
    }
};
