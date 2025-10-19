<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rm_payments', function (Blueprint $table) {
            // Add cost_item_id to link payments to specific cost items
            $table->unsignedBigInteger('cost_item_id')->nullable()->after('installment_id');

            // Add installment_sequence to track which installment number this payment belongs to
            $table->integer('installment_sequence')->nullable()->after('cost_item_id')
                ->comment('Which installment payment this applies to (1st, 2nd, 3rd, etc.)');

            // Add foreign key constraint
            $table->foreign('cost_item_id')
                  ->references('id')
                  ->on('rental_cost_items')
                  ->onDelete('set null');

            // Add index for better query performance
            $table->index(['rental_id', 'cost_item_id']);
            $table->index(['rental_id', 'installment_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rm_payments', function (Blueprint $table) {
            $table->dropForeign(['cost_item_id']);
            $table->dropIndex(['rental_id', 'cost_item_id']);
            $table->dropIndex(['rental_id', 'installment_sequence']);
            $table->dropColumn(['cost_item_id', 'installment_sequence']);
        });
    }
};

