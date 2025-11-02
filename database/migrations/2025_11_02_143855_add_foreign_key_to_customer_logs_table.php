<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, clean up orphaned records (customer_logs with customer_id that doesn't exist in api_customers)
        DB::statement('
            DELETE FROM customer_logs
            WHERE customer_id NOT IN (SELECT id FROM api_customers)
        ');

        Schema::table('customer_logs', function (Blueprint $table) {
            // Add foreign key constraint with cascade delete
            $table->foreign('customer_id')
                ->references('id')
                ->on('api_customers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['customer_id']);
        });
    }
};
