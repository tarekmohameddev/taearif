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
     * Fix the api_customer_inquiry foreign key constraint.
     * The CASCADE rule was not working properly, so we drop and recreate it.
     */
    public function up(): void
    {
        // Check if the foreign key exists before trying to drop it
        $fkExists = DB::selectOne("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'api_customer_inquiry'
            AND CONSTRAINT_NAME = 'api_customer_inquiry_customer_id_foreign'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");

        if ($fkExists) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }

        // Clean up orphaned records (inquiries with non-existent customers)
        DB::statement("
            DELETE FROM api_customer_inquiry
            WHERE customer_id NOT IN (SELECT id FROM api_customers)
        ");

        // Ensure column types match
        // Get the column type of api_customers.id
        $customerIdType = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'api_customers'
            AND COLUMN_NAME = 'id'
        ");

        $inquiryCustomerIdType = DB::selectOne("
            SELECT COLUMN_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'api_customer_inquiry'
            AND COLUMN_NAME = 'customer_id'
        ");

        // If types don't match, fix it
        if ($customerIdType && $inquiryCustomerIdType &&
            $customerIdType->COLUMN_TYPE !== $inquiryCustomerIdType->COLUMN_TYPE) {
            DB::statement("
                ALTER TABLE api_customer_inquiry
                MODIFY customer_id {$customerIdType->COLUMN_TYPE}
            ");
        }

        // CRITICAL: Add index on customer_id if it doesn't exist
        // MySQL requires an index on foreign key columns
        // Use raw SQL to ensure it works
        try {
            DB::statement("
                ALTER TABLE api_customer_inquiry
                ADD INDEX api_customer_inquiry_customer_id_index (customer_id)
            ");
        } catch (\Exception $e) {
            // Index might already exist, that's OK
            if (!str_contains($e->getMessage(), 'Duplicate key name')) {
                throw $e;
            }
        }

        // Now recreate the foreign key with proper CASCADE using raw SQL
        DB::statement("
            ALTER TABLE api_customer_inquiry
            ADD CONSTRAINT api_customer_inquiry_customer_id_foreign
            FOREIGN KEY (customer_id)
            REFERENCES api_customers(id)
            ON DELETE CASCADE
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop and recreate without cascade (revert to original state)
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('api_customers')
                ->onDelete('cascade');
        });
    }
};
