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
        Schema::table('api_employee_activity_logs', function (Blueprint $table) {
            // Index for tenant filtering by employee activity (most common query pattern)
            $table->index(['user_id', 'actor_type', 'actor_id'], 'emp_activity_tenant_employee_idx');

            // Index for time-based queries within tenant/employee scope
            $table->index(['user_id', 'actor_type', 'actor_id', 'created_at'], 'emp_activity_tenant_employee_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_employee_activity_logs', function (Blueprint $table) {
            $table->dropIndex('emp_activity_tenant_employee_idx');
            $table->dropIndex('emp_activity_tenant_employee_time_idx');
        });
    }
};
