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
        Schema::table('marketing_channels', function (Blueprint $table) {
            // System Integration Settings
            $table->boolean('crm_integration_enabled')->default(false)->after('additional_settings');
            $table->boolean('appointment_system_integration_enabled')->default(false)->after('crm_integration_enabled');
            $table->json('integration_settings')->nullable()->after('appointment_system_integration_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->dropColumn([
                'crm_integration_enabled',
                'appointment_system_integration_enabled',
                'integration_settings'
            ]);
        });
    }
};
