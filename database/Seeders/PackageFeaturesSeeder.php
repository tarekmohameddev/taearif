<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Package;


class PackageFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $freePackage = Package::find(16);
        $freePackage->update([
            'new_features' => json_encode([
                '10 عقارات',
                'لا يوجد مشاريع',
                'عدم القدرة على اضافة دومين مخصص',
            ], JSON_UNESCAPED_UNICODE)
        ]);

        $premiumPackage = Package::find(24);
        $premiumPackage->update([
            'new_features' => json_encode([
                'عدد غير محدود للعقارات',
                'عدد غير محدود للمشاريع',
                'التحكم في دومين مخصص',
                'دعم واتس اب',
                'تخصيص الموقع',
            ], JSON_UNESCAPED_UNICODE)
        ]);
    }
}
