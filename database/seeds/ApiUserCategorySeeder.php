<?php

namespace Database\seeds;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User\RealestateManagement\ApiUserCategory;


class ApiUserCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'فيلا',
            'شقة في برج',
            'شقة في عمارة',
            'أرض',
            'قصر',
            'مزرعة',
            'استراحة',
            'محل تجاري',
            'مكتب',
            'منتجع',
            'معرض',
            'مبنى',
            'دور في فيلا',
        ];

        foreach ($categories as $name) {
            ApiUserCategory::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'type' => 'property',
                    'is_active' => 1,
                ]
            );
        }
    }
}

