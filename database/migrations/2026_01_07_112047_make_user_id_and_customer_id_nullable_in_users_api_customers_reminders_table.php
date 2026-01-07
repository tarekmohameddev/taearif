<?php

use Illuminate\Database\Migrations\Migration;
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
        // Drop foreign key constraints first (with error handling)
        try {
            DB::statement("
                ALTER TABLE users_api_customers_reminders
                DROP FOREIGN KEY users_api_customers_reminders_user_id_foreign
            ");
        } catch (\Throwable $e) {
            // Ignore if constraint doesn't exist
        }
        
        try {
            DB::statement("
                ALTER TABLE users_api_customers_reminders
                DROP FOREIGN KEY users_api_customers_reminders_customer_id_foreign
            ");
        } catch (\Throwable $e) {
            // Ignore if constraint doesn't exist
        }
        
        // Make user_id nullable
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY user_id BIGINT UNSIGNED NULL
        ");
        
        // Make customer_id nullable
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY customer_id BIGINT UNSIGNED NULL
        ");
        
        // Re-add foreign key constraints
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            ADD CONSTRAINT users_api_customers_reminders_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ");
        
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            ADD CONSTRAINT users_api_customers_reminders_customer_id_foreign
            FOREIGN KEY (customer_id) REFERENCES api_customers(id) ON DELETE CASCADE
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraints (with error handling)
        try {
            DB::statement("
                ALTER TABLE users_api_customers_reminders
                DROP FOREIGN KEY users_api_customers_reminders_user_id_foreign
            ");
        } catch (\Throwable $e) {
            // Ignore if constraint doesn't exist
        }
        
        try {
            DB::statement("
                ALTER TABLE users_api_customers_reminders
                DROP FOREIGN KEY users_api_customers_reminders_customer_id_foreign
            ");
        } catch (\Throwable $e) {
            // Ignore if constraint doesn't exist
        }
        
        // Revert customer_id to NOT NULL
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY customer_id BIGINT UNSIGNED NOT NULL
        ");
        
        // Revert user_id to NOT NULL
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            MODIFY user_id BIGINT UNSIGNED NOT NULL
        ");
        
        // Re-add foreign key constraints
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            ADD CONSTRAINT users_api_customers_reminders_user_id_foreign
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ");
        
        DB::statement("
            ALTER TABLE users_api_customers_reminders
            ADD CONSTRAINT users_api_customers_reminders_customer_id_foreign
            FOREIGN KEY (customer_id) REFERENCES api_customers(id) ON DELETE CASCADE
        ");
    }
};
