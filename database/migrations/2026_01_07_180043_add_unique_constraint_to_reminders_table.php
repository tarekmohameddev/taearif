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
