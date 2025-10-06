<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->boolean('customers_page_integration_enabled')->default(false)->after('appointment_system_integration_enabled');
            $table->boolean('rental_page_integration_enabled')->default(false)->after('customers_page_integration_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_channels', function (Blueprint $table) {
            $table->dropColumn([
                'customers_page_integration_enabled',
                'rental_page_integration_enabled',
            ]);
        });
    }
};


