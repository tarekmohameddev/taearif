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

        // Recreate the foreign key with proper CASCADE
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->foreign('customer_id')
                ->references('id')
                ->on('api_customers')
                ->onDelete('cascade');
        });
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
