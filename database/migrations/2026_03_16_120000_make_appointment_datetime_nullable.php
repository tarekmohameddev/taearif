<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->dateTime('datetime')->nullable()->change();
        });

        Schema::table('inquiry_appointments', function (Blueprint $table) {
            $table->dateTime('datetime')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->dateTime('datetime')->nullable(false)->change();
        });

        Schema::table('inquiry_appointments', function (Blueprint $table) {
            $table->dateTime('datetime')->nullable(false)->change();
        });
    }
};

