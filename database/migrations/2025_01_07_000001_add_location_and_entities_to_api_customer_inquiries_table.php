<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            // Monetary / preference fields
            $table->string('currency', 8)->nullable()->after('budget');
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('currency');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
            $table->decimal('min_area_sqm', 10, 2)->nullable()->after('bathrooms');
            $table->decimal('max_area_sqm', 10, 2)->nullable()->after('min_area_sqm');
            $table->boolean('furnished')->nullable()->after('max_area_sqm');
            $table->string('urgency', 16)->nullable()->after('furnished');

            // Normalized location
            $table->string('country_code', 2)->nullable()->after('location');
            $table->string('region_code', 4)->nullable()->after('country_code');
            $table->string('region_name', 64)->nullable()->after('region_code');
            $table->string('city', 128)->nullable()->after('region_name');
            $table->string('district', 128)->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('district');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('location_confidence', 3, 2)->nullable()->after('longitude'); // 0.00–1.00

            // Meta
            $table->string('source_channel', 32)->nullable()->after('location_confidence'); // whatsapp/webchat/...
            $table->string('lang', 8)->nullable()->after('source_channel'); // ar/en

            // Optional: keep full entities json for debugging/audit
            $table->json('detected_entities_json')->nullable()->after('lang');
        });
    }

    public function down(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropColumn([
                'currency','bedrooms','bathrooms','min_area_sqm','max_area_sqm','furnished','urgency',
                'country_code','region_code','region_name','city','district','latitude','longitude','location_confidence',
                'source_channel','lang','detected_entities_json',
            ]);
        });
    }
};
