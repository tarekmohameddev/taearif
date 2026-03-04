<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns from api_customer_inquiry that have no equivalent
     * on users_property_requests (responsible_employee_id, inquiry_type,
     * currency, bedrooms, bathrooms, furnished, location, country_code,
     * region_code, latitude, longitude, location_confidence, lang,
     * detected_entities_json).
     */
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'responsible_employee_id')) {
                $table->unsignedBigInteger('responsible_employee_id')->nullable()->after('status_id');
            }
            if (!Schema::hasColumn('users_property_requests', 'inquiry_type')) {
                $table->string('inquiry_type')->nullable()->after('customers_hub_stage_id');
            }
            if (!Schema::hasColumn('users_property_requests', 'currency')) {
                $table->string('currency', 8)->nullable()->after('budget_to');
            }
            if (!Schema::hasColumn('users_property_requests', 'bedrooms')) {
                $table->unsignedTinyInteger('bedrooms')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('users_property_requests', 'bathrooms')) {
                $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
            }
            if (!Schema::hasColumn('users_property_requests', 'furnished')) {
                $table->boolean('furnished')->nullable()->after('bathrooms');
            }
            if (!Schema::hasColumn('users_property_requests', 'location')) {
                $table->string('location')->nullable()->after('region');
            }
            if (!Schema::hasColumn('users_property_requests', 'country_code')) {
                $table->string('country_code', 2)->nullable()->after('location');
            }
            if (!Schema::hasColumn('users_property_requests', 'region_code')) {
                $table->string('region_code', 4)->nullable()->after('country_code');
            }
            if (!Schema::hasColumn('users_property_requests', 'city')) {
                $table->string('city', 128)->nullable()->after('city_id');
            }
            if (!Schema::hasColumn('users_property_requests', 'district')) {
                $table->string('district', 128)->nullable()->after('districts_id');
            }
            if (!Schema::hasColumn('users_property_requests', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('district');
            }
            if (!Schema::hasColumn('users_property_requests', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('users_property_requests', 'location_confidence')) {
                $table->decimal('location_confidence', 3, 2)->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('users_property_requests', 'lang')) {
                $table->string('lang', 8)->nullable()->after('referral_source');
            }
            if (!Schema::hasColumn('users_property_requests', 'detected_entities_json')) {
                $table->json('detected_entities_json')->nullable()->after('notes');
            }
        });

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'responsible_employee_id')) {
                $table->foreign('responsible_employee_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
                $table->index('responsible_employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'responsible_employee_id')) {
                $table->dropForeign(['responsible_employee_id']);
                $table->dropIndex(['responsible_employee_id']);
                $table->dropColumn('responsible_employee_id');
            }
        });

        $columns = [
            'inquiry_type',
            'currency',
            'bedrooms',
            'bathrooms',
            'furnished',
            'location',
            'country_code',
            'region_code',
            'city',
            'district',
            'latitude',
            'longitude',
            'location_confidence',
            'lang',
            'detected_entities_json',
        ];
        $toDrop = array_filter($columns, fn (string $col): bool => Schema::hasColumn('users_property_requests', $col));
        if ($toDrop !== []) {
            Schema::table('users_property_requests', function (Blueprint $table) use ($toDrop) {
                $table->dropColumn($toDrop);
            });
        }
    }
};
