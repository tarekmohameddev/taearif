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
        // Update existing NULL or empty currency values to 'SAR'
        DB::table('rm_rentals')
            ->whereNull('currency')
            ->orWhere('currency', '')
            ->update(['currency' => 'SAR']);

        // Ensure the currency column has 'SAR' as default
        Schema::table('rm_rentals', function (Blueprint $table) {
            $table->char('currency', 3)->default('SAR')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No need to revert as this is ensuring proper defaults
        Schema::table('rm_rentals', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->change();
        });
    }
};
