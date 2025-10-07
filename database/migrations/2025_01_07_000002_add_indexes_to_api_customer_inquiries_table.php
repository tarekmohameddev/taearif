<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->index('inquiry_type', 'aci_inquiry_type_idx');
            $table->index('property_type', 'aci_property_type_idx');

            $table->index('region_code', 'aci_region_code_idx');
            $table->index('city', 'aci_city_idx');
            $table->index(['region_code', 'city'], 'aci_region_city_idx');

            $table->index('created_at', 'aci_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropIndex('aci_inquiry_type_idx');
            $table->dropIndex('aci_property_type_idx');
            $table->dropIndex('aci_region_code_idx');
            $table->dropIndex('aci_city_idx');
            $table->dropIndex('aci_region_city_idx');
            $table->dropIndex('aci_created_at_idx');
        });
    }
};
