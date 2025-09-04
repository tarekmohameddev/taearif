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
        Schema::table('rm_payment_installments', function (Blueprint $table) {
            $table->enum('payment_type', ['none', 'partial', 'full'])->default('none')->after('status');
            $table->enum('payment_status', ['not_due', 'paid_in_full', 'paid_in_part', 'late'])->default('not_due')->after('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rm_payment_installments', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'payment_status']);
        });
    }
};
