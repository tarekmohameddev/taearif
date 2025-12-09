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
        // Legacy table - only create if api_employees table exists
        // This table is deprecated and will be dropped by later migrations
        // Note: api_employees table is legacy and has been replaced by users table with account_type='employee'
        if (!Schema::hasTable('api_employee_role') && Schema::hasTable('api_employees')) {
            Schema::create('api_employee_role', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->primary(['employee_id', 'role_id']);
                $table->foreign('employee_id')->references('id')->on('api_employees')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('api_roles')->onDelete('cascade');
            });
        }

        // Ensure users table has the necessary columns for employees
        if (Schema::hasTable('users')) {
            // Add account_type column if it doesn't exist
            if (!Schema::hasColumn('users', 'account_type')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('account_type')->default('tenant')->after('tenant_id');
                });
            }
            
            // Add tenant_id column if it doesn't exist
            if (!Schema::hasColumn('users', 'tenant_id')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Only drop the table if it exists
        if (Schema::hasTable('api_employee_role')) {
            Schema::dropIfExists('api_employee_role');
        }
    }
};
