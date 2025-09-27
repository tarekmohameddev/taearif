<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Api\markting\CreditPackage;

class CreditPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages = [
            [
                'name' => 'Basic Package',
                'name_ar' => 'الباقة الأساسية',
                'description' => 'Perfect for small businesses and personal use',
                'description_ar' => 'مثالية للشركات الصغيرة والاستخدام الشخصي',
                'credits' => 1000,
                'price' => 50.00,
                'currency' => 'SAR',
                'discount_percentage' => null,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    '1000 message credits',
                    'WhatsApp integration',
                    'Basic analytics',
                    'Email support',
                ],
            ],
            [
                'name' => 'Medium Package',
                'name_ar' => 'الباقة المتوسطة',
                'description' => 'Great for growing businesses with regular messaging needs',
                'description_ar' => 'مثالية للشركات النامية مع احتياجات الرسائل المنتظمة',
                'credits' => 2500,
                'price' => 100.00,
                'currency' => 'SAR',
                'discount_percentage' => 20.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    '2500 message credits',
                    'WhatsApp & SMS integration',
                    'Advanced analytics',
                    'Priority support',
                    'Save 20%',
                ],
            ],
            [
                'name' => 'Advanced Package',
                'name_ar' => 'الباقة المتقدمة',
                'description' => 'Most popular choice for businesses with high messaging volume',
                'description_ar' => 'الخيار الأكثر شعبية للشركات مع حجم رسائل عالي',
                'credits' => 5000,
                'price' => 180.00,
                'currency' => 'SAR',
                'discount_percentage' => 28.00,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    '5000 message credits',
                    'All channel integrations',
                    'Comprehensive analytics',
                    '24/7 support',
                    'Save 28%',
                    'Most Popular',
                ],
            ],
            [
                'name' => 'Professional Package',
                'name_ar' => 'الباقة الاحترافية',
                'description' => 'For large enterprises with extensive messaging requirements',
                'description_ar' => 'للشركات الكبيرة مع متطلبات الرسائل الواسعة',
                'credits' => 10000,
                'price' => 300.00,
                'currency' => 'SAR',
                'discount_percentage' => 40.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
                'features' => [
                    '10000 message credits',
                    'All channel integrations',
                    'Advanced analytics & reporting',
                    'Dedicated account manager',
                    'Custom integrations',
                    'Save 40%',
                ],
            ],
        ];

        foreach ($packages as $packageData) {
            CreditPackage::create($packageData);
        }

        $this->command->info('Credit packages seeded successfully!');
        $this->command->info('Created ' . count($packages) . ' credit packages.');
    }
}
