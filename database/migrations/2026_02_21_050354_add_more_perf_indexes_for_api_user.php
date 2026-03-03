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
        Schema::table('employee_addons', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'expire_date'], 'emp_addons_user_status_expire_idx');
        });

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'wa_users_user_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_addons', function (Blueprint $table) {
            $table->dropIndex('emp_addons_user_status_expire_idx');
        });

        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropIndex('wa_users_user_status_idx');
        });
    }
};
