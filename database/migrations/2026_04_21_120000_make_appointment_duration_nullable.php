<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->unsignedInteger('duration')->nullable()->default(null)->comment('minutes')->change();
        });

        Schema::table('inquiry_appointments', function (Blueprint $table) {
            $table->unsignedInteger('duration')->nullable()->default(null)->comment('minutes')->change();
        });
    }

    public function down(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->unsignedInteger('duration')->nullable(false)->default(30)->comment('minutes')->change();
        });

        Schema::table('inquiry_appointments', function (Blueprint $table) {
            $table->unsignedInteger('duration')->nullable(false)->default(30)->comment('minutes')->change();
        });
    }
};

