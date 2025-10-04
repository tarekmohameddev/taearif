<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rm_rentals', function (Blueprint $table) {
            // Add new fields only if they don't exist
            if (!Schema::hasColumn('rm_rentals', 'rental_type')) {
                $table->enum('rental_type', ['monthly', 'annual'])->nullable()->after('unit_label');
            }
            if (!Schema::hasColumn('rm_rentals', 'rental_duration')) {
                $table->integer('rental_duration')->nullable()->after('rental_type');
            }
            if (!Schema::hasColumn('rm_rentals', 'total_rental_amount')) {
                $table->decimal('total_rental_amount', 15, 2)->nullable()->after('base_rent_amount');
            }
            
            // Remove old fields only if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('rm_rentals', 'deposit_amount')) {
                $columnsToDrop[] = 'deposit_amount';
            }
            if (Schema::hasColumn('rm_rentals', 'platform_fee')) {
                $columnsToDrop[] = 'platform_fee';
            }
            if (Schema::hasColumn('rm_rentals', 'water_fee')) {
                $columnsToDrop[] = 'water_fee';
            }
            if (Schema::hasColumn('rm_rentals', 'office_commission_type')) {
                $columnsToDrop[] = 'office_commission_type';
            }
            if (Schema::hasColumn('rm_rentals', 'office_commission_value')) {
                $columnsToDrop[] = 'office_commission_value';
            }
            if (Schema::hasColumn('rm_rentals', 'office_fee')) {
                $columnsToDrop[] = 'office_fee';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rm_rentals', function (Blueprint $table) {
            // Remove new fields only if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('rm_rentals', 'rental_type')) {
                $columnsToDrop[] = 'rental_type';
            }
            if (Schema::hasColumn('rm_rentals', 'rental_duration')) {
                $columnsToDrop[] = 'rental_duration';
            }
            if (Schema::hasColumn('rm_rentals', 'total_rental_amount')) {
                $columnsToDrop[] = 'total_rental_amount';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
            
            // Add back old fields only if they don't exist
            if (!Schema::hasColumn('rm_rentals', 'deposit_amount')) {
                $table->decimal('deposit_amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('rm_rentals', 'platform_fee')) {
                $table->decimal('platform_fee', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('rm_rentals', 'water_fee')) {
                $table->decimal('water_fee', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('rm_rentals', 'office_commission_type')) {
                $table->enum('office_commission_type', ['percentage', 'amount'])->nullable();
            }
            if (!Schema::hasColumn('rm_rentals', 'office_commission_value')) {
                $table->decimal('office_commission_value', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('rm_rentals', 'office_fee')) {
                $table->decimal('office_fee', 12, 2)->nullable();
            }
        });
    }
};
