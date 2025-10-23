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
     * Add 'cancelled' as valid enum value for status and payment_status columns
     */
    public function up(): void
    {
        // Modify status enum to add 'cancelled'
        DB::statement("ALTER TABLE `rm_payment_installments`
            MODIFY COLUMN `status` ENUM('pending', 'paid', 'partial', 'overdue', 'void', 'cancelled')
            NOT NULL DEFAULT 'pending'");

        // Modify payment_status enum to add 'cancelled'
        DB::statement("ALTER TABLE `rm_payment_installments`
            MODIFY COLUMN `payment_status` ENUM('not_due', 'paid_in_full', 'paid_in_part', 'late', 'cancelled')
            NOT NULL DEFAULT 'not_due'");
    }

    /**
     * Reverse the migrations.
     *
     * Remove 'cancelled' from enum values
     */
    public function down(): void
    {
        // First, update any 'cancelled' values to 'void' or 'not_due'
        DB::statement("UPDATE `rm_payment_installments` SET `status` = 'void' WHERE `status` = 'cancelled'");
        DB::statement("UPDATE `rm_payment_installments` SET `payment_status` = 'not_due' WHERE `payment_status` = 'cancelled'");

        // Revert status enum
        DB::statement("ALTER TABLE `rm_payment_installments`
            MODIFY COLUMN `status` ENUM('pending', 'paid', 'partial', 'overdue', 'void')
            NOT NULL DEFAULT 'pending'");

        // Revert payment_status enum
        DB::statement("ALTER TABLE `rm_payment_installments`
            MODIFY COLUMN `payment_status` ENUM('not_due', 'paid_in_full', 'paid_in_part', 'late')
            NOT NULL DEFAULT 'not_due'");
    }
};
