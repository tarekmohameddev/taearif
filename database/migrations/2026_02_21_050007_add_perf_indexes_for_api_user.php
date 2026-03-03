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
        Schema::table('api_model_has_roles', function (Blueprint $table) {
            $table->index(['team_id', 'model_id', 'model_type'], 'api_mhr_team_model_type_idx');
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->index(['whatsapp_number_id', 'status', 'expire_date'], 'wa_addons_number_status_expire_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_model_has_roles', function (Blueprint $table) {
            $table->dropIndex('api_mhr_team_model_type_idx');
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropIndex('wa_addons_number_status_expire_idx');
        });
    }
};
