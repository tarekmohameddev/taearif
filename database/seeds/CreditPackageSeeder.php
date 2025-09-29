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
                'name' => 'Starter Package',
                'name_ar' => 'باقة المبتدئين',
                'description' => 'Perfect for small businesses getting started with marketing automation',
                'description_ar' => 'مثالية للشركات الصغيرة التي تبدأ في أتمتة التسويق',
                'credits' => 100,
                'price' => 50.00,
                'currency' => 'SAR',
                'discount_percentage' => null,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    '100 SMS/WhatsApp messages',
                    'Basic analytics',
                    'Email support'
                ],
            ],
            [
                'name' => 'Business Package',
                'name_ar' => 'باقة الأعمال',
                'description' => 'Ideal for growing businesses with regular marketing needs',
                'description_ar' => 'مثالية للشركات النامية ذات الاحتياجات التسويقية المنتظمة',
                'credits' => 500,
                'price' => 200.00,
                'currency' => 'SAR',
                'discount_percentage' => 20.00,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    '500 SMS/WhatsApp messages',
                    'Advanced analytics',
                    'Priority support',
                    'Multi-channel campaigns'
                ],
            ],
            [
                'name' => 'Professional Package',
                'name_ar' => 'الباقة المهنية',
                'description' => 'For established businesses with high-volume marketing requirements',
                'description_ar' => 'للشركات الراسخة ذات متطلبات التسويق عالية الحجم',
                'credits' => 1000,
                'price' => 350.00,
                'currency' => 'SAR',
                'discount_percentage' => 30.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    '1000 SMS/WhatsApp messages',
                    'Comprehensive analytics',
                    '24/7 support',
                    'Advanced automation',
                    'Custom integrations'
                ],
            ],
            [
                'name' => 'Enterprise Package',
                'name_ar' => 'باقة المؤسسات',
                'description' => 'For large enterprises with extensive marketing operations',
                'description_ar' => 'للمؤسسات الكبيرة ذات العمليات التسويقية الواسعة',
                'credits' => 2500,
                'price' => 750.00,
                'currency' => 'SAR',
                'discount_percentage' => 40.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
                'features' => [
                    '2500 SMS/WhatsApp messages',
                    'Enterprise analytics',
                    'Dedicated account manager',
                    'Full automation suite',
                    'API access',
                    'Custom reporting'
                ],
            ],
            [
                'name' => 'Mega Package',
                'name_ar' => 'الباقة الضخمة',
                'description' => 'Maximum value for high-volume users with unlimited potential',
                'description_ar' => 'أقصى قيمة للمستخدمين عاليي الحجم مع إمكانيات غير محدودة',
                'credits' => 5000,
                'price' => 1200.00,
                'currency' => 'SAR',
                'discount_percentage' => 50.00,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 5,
                'features' => [
                    '5000 SMS/WhatsApp messages',
                    'Premium analytics',
                    'White-label options',
                    'Custom development',
                    'Unlimited integrations',
                    'Priority everything'
                ],
            ],
        ];

        foreach ($packages as $packageData) {
            CreditPackage::create($packageData);
        }

        $this->command->info('Credit packages seeded successfully!');
    }
}