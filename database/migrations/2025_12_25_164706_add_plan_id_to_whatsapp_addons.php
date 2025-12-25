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
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('whatsapp_number_id')->constrained('whatsapp_addon_plans');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropColumn('plan_id');
        });
    }
};
