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
        Schema::table('user_properties', function (Blueprint $table) {
            $table->enum('payment_method', ['monthly', 'quarterly', 'semi_annual', 'annual'])->nullable()->after('region_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
