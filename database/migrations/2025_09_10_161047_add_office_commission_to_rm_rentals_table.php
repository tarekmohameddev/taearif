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
            $table->enum('office_commission_type', ['percentage', 'amount'])->nullable()->after('water_fee');
            $table->decimal('office_commission_value', 12, 2)->nullable()->after('office_commission_type');
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
            $table->dropColumn(['office_commission_type', 'office_commission_value']);
        });
    }
};