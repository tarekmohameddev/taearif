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
        // Remove duplicate entries, keeping only the oldest one (lowest id)
        // This handles duplicates where user_id and customer_id are NOT NULL
        DB::statement("
            DELETE r1 FROM users_api_customers_reminders r1
            INNER JOIN users_api_customers_reminders r2
            WHERE r1.id > r2.id
            AND r1.user_id = r2.user_id
            AND r1.customer_id = r2.customer_id
            AND r1.title = r2.title
            AND r1.user_id IS NOT NULL
            AND r1.customer_id IS NOT NULL
        ");
        
        // Add unique index on (user_id, customer_id, title)
        // This prevents duplicate reminders for the same customer with the same title
        // Note: MySQL treats NULL values specially - multiple NULLs are allowed in unique indexes
        // So reminders with customer_id=NULL (general reminders) can have duplicate titles,
        // but reminders with customer_id IS NOT NULL will enforce uniqueness
        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->unique(['user_id', 'customer_id', 'title'], 'reminders_user_customer_title_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->dropUnique('reminders_user_customer_title_unique');
        });
    }
};
