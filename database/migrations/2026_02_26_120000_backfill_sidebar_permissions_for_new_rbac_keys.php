<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('api_sidebar_items')
            ->where('path', '/property-requests')
            ->update(['permission' => 'property_requests.view']);

        DB::table('api_sidebar_items')
            ->where('path', '/matching')
            ->update(['permission' => 'property_requests.view']);

        DB::table('api_sidebar_items')
            ->where('path', '/rental-management')
            ->update(['permission' => 'rentals.view']);

        DB::table('api_sidebar_items')->updateOrInsert(
            ['path' => '/buildings'],
            [
                'title' => 'المباني',
                'description' => 'ادارة المباني',
                'icon' => 'building',
                'permission' => 'buildings.view',
                'condition_type' => null,
                'order' => 14,
                'is_active' => true,
            ]
        );

        DB::table('api_sidebar_items')->updateOrInsert(
            ['path' => '/job-applications'],
            [
                'title' => 'طلبات الوظائف',
                'description' => 'ادارة طلبات الوظائف',
                'icon' => 'briefcase',
                'permission' => 'job_applications.view',
                'condition_type' => null,
                'order' => 15,
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        // Non-destructive rollback by design for production safety.
    }
};

