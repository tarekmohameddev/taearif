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
        // First, ensure the target columns exist in rm_rentals
        if (!Schema::hasColumn('rm_rentals', 'office_commission_type')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->enum('office_commission_type', ['percentage', 'amount'])->nullable()->after('water_fee');
            });
        }
        
        if (!Schema::hasColumn('rm_rentals', 'office_commission_value')) {
            Schema::table('rm_rentals', function (Blueprint $table) {
                $table->decimal('office_commission_value', 12, 2)->nullable()->after('office_commission_type');
            });
        }

        // Migrate data from contracts to rentals (latest contract per rental)
        DB::statement("
            UPDATE rm_rentals r
            INNER JOIN (
                SELECT
                    rental_id,
                    office_commission_type,
                    office_commission_value,
                    ROW_NUMBER() OVER (PARTITION BY rental_id ORDER BY created_at DESC) as rn
                FROM rm_contracts
                WHERE office_commission_type IS NOT NULL OR office_commission_value IS NOT NULL
            ) c ON r.id = c.rental_id AND c.rn = 1
            SET
                r.office_commission_type = c.office_commission_type,
                r.office_commission_value = c.office_commission_value
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Clear the office commission fields in rentals
        DB::statement("UPDATE rm_rentals SET office_commission_type = NULL, office_commission_value = NULL");
    }
};