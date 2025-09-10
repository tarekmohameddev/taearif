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
        Schema::table('rm_contracts', function (Blueprint $table) {
            $table->dropColumn(['platform_fee', 'water_fee_monthly']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rm_contracts', function (Blueprint $table) {
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->decimal('water_fee_monthly', 10, 2)->nullable();
        });
    }
};
