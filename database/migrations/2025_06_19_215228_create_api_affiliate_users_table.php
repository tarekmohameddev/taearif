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
        Schema::create('api_affiliate_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('fullname');
            $table->string('bank_name');
            $table->string('bank_account_number');
            $table->string('iban');
            $table->decimal('commission_percentage', 5, 2)->default(0.15); // Default 15%
            $table->decimal('total_commission', 10, 2)->default(0.00);
            $table->decimal('withdrawn_amount', 10, 2)->default(0.00);
            $table->decimal('pending_amount', 10, 2)->default(0.00);
            $table->decimal('total_earned', 10, 2)->default(0.00);
            $table->decimal('total_withdrawn', 10, 2)->default(0.00);
            $table->decimal('total_pending', 10, 2)->default(0.00);
            $table->decimal('total_refunded', 10, 2)->default(0.00);
            $table->decimal('total_commission_paid', 10, 2)->default(0.00);
            $table->decimal('total_commission_pending', 10, 2)->default(0.00);
            $table->enum('request_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_affiliate_users');
    }
};
