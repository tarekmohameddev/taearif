<?php

use App\Support\CacheInvalidationHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            [
                'slug' => 'duplex',
                'name' => 'دوبلكس',
            ],
            [
                'slug' => 'townhouse',
                'name' => 'تاون هاوس',
            ],
            [
                'slug' => 'room',
                'name' => 'غرفة',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('api_user_categories')->updateOrInsert(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'type' => 'property',
                    'is_active' => 1,
                    'icon' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        CacheInvalidationHelper::clearPropertyCategoriesCache();
    }

    public function down(): void
    {
        DB::table('api_user_categories')
            ->whereIn('slug', ['duplex', 'townhouse', 'room'])
            ->delete();

        CacheInvalidationHelper::clearPropertyCategoriesCache();
    }
};

