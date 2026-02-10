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
        Schema::table('api_customers', function (Blueprint $table) {
            // Add soft deletes column
            $table->softDeletes();
            
            // Add composite indexes for performance
            $table->index(['user_id', 'deleted_at'], 'idx_api_customers_user_deleted');
            $table->index(['responsible_employee_id', 'deleted_at'], 'idx_api_customers_employee_deleted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_customers', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_api_customers_user_deleted');
            $table->dropIndex('idx_api_customers_employee_deleted');
            
            // Drop soft deletes column
            $table->dropSoftDeletes();
        });
    }
};
