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
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->unsignedInteger('attempts')->default(0)->after('otp_expires_at');
            $table->timestamp('verified_at')->nullable()->after('attempts');
            $table->string('context')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('otp_verifications', function (Blueprint $table) {
            $table->dropColumn(['attempts', 'verified_at', 'context']);
        });
    }
};
