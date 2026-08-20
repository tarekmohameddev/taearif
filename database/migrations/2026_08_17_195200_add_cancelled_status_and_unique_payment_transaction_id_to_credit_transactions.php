<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement(
            "ALTER TABLE credit_transactions MODIFY status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending'"
        );

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->unique(
                'payment_transaction_id',
                'credit_transactions_payment_transaction_id_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropUnique('credit_transactions_payment_transaction_id_unique');
        });
    }
};
