<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $mapping = [
        'new' => [
            'name_ar'       => 'معلق',
            'name_en'       => 'Suspended',
            'slug'          => 'suspended',
            'display_order' => 1,
        ],
        'follow_up' => [
            'name_ar'       => 'جاري العمل',
            'name_en'       => 'In Progress',
            'slug'          => 'in_progress',
            'display_order' => 2,
        ],
        'property_found' => [
            'name_ar'       => 'جاري الانتظار',
            'name_en'       => 'Waiting',
            'slug'          => 'waiting',
            'display_order' => 3,
        ],
        'contract_signed' => [
            'name_ar'       => 'مكتمل',
            'name_en'       => 'Completed',
            'slug'          => 'completed',
            'display_order' => 4,
        ],
        'cancelled' => [
            'name_ar'       => 'ملغي',
            'name_en'       => 'Cancelled',
            'slug'          => 'cancelled',
            'display_order' => 5,
        ],
    ];

    private array $rollback = [
        'suspended' => [
            'name_ar'       => 'جديد',
            'name_en'       => 'New',
            'slug'          => 'new',
            'display_order' => 1,
        ],
        'in_progress' => [
            'name_ar'       => 'متابعة',
            'name_en'       => 'Follow Up',
            'slug'          => 'follow_up',
            'display_order' => 2,
        ],
        'waiting' => [
            'name_ar'       => 'تم العثور على عقار',
            'name_en'       => 'Property Found',
            'slug'          => 'property_found',
            'display_order' => 3,
        ],
        'completed' => [
            'name_ar'       => 'تم التعاقد',
            'name_en'       => 'Contract Signed',
            'slug'          => 'contract_signed',
            'display_order' => 4,
        ],
        'cancelled' => [
            'name_ar'       => 'ملغي',
            'name_en'       => 'Cancelled',
            'slug'          => 'cancelled',
            'display_order' => 5,
        ],
    ];

    public function up(): void
    {
        foreach ($this->mapping as $currentSlug => $newValues) {
            DB::table('property_request_statuses')
                ->where('slug', $currentSlug)
                ->update(array_merge($newValues, ['updated_at' => now()]));
        }
    }

    public function down(): void
    {
        foreach ($this->rollback as $currentSlug => $oldValues) {
            DB::table('property_request_statuses')
                ->where('slug', $currentSlug)
                ->update(array_merge($oldValues, ['updated_at' => now()]));
        }
    }
};
