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
            $table->decimal('pending_amount', 10, 2)->default(0.00);
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
