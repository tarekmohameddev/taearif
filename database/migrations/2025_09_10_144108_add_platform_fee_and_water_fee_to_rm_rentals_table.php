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
            $table->decimal('platform_fee', 10, 2)->nullable()->after('deposit_amount');
            $table->decimal('water_fee', 10, 2)->nullable()->after('platform_fee');
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
            $table->dropColumn(['platform_fee', 'water_fee']);
        });
    }
};
