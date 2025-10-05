<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rm_payments', function (Blueprint $table) {
            $table->string('bank_name', 100)->nullable()->after('payment_method');
            $table->string('receipt_image_path', 500)->nullable()->after('bank_name');
            $table->enum('transfer_to', ['منصة ناجز', 'المالك', 'المكتب'])->after('receipt_image_path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rm_payments', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'receipt_image_path', 'transfer_to']);
        });
    }
};
