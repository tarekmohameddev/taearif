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
        Schema::table('affiliate_transactions', function (Blueprint $table) {
            $table->foreignId('referral_user_id')
                  ->nullable()
                  ->after('affiliate_id')
                  ->constrained('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('affiliate_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referral_user_id');
        });
    }
};
