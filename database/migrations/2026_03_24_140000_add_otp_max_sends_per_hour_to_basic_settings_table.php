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
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('otp_max_sends_per_hour')
                ->default(5)
                ->after('registration_otp_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn('otp_max_sends_per_hour');
        });
    }
};

