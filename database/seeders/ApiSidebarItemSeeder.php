<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Api\ApiSidebarItem;

class ApiSidebarItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $items = [
            [
                'title' => 'لوحة التحكم',
                'description' => 'نظره عامه عن الموقع',
                'icon' => 'panel',
                'path' => '/',
                'permission' => null,
                'condition_type' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'title' => 'اعدادات الموقع',
                'description' => 'تكوين اعدادات الموقع',
                'icon' => 'web-settings',
                'path' => '/settings',
                'permission' => 'settings.view',
                'condition_type' => null,
                'order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'ادارة العملاء',
                'description' => 'ادارة عملائك',
                'icon' => 'users',
                'path' => '/customers',
                'permission' => 'customers.view',
                'condition_type' => null,
                'order' => 3,
                'is_active' => true,
            ],
            [
                'title' => 'CRM',
                'description' => 'تكوين اعدادات ادارة علاقات العملاء',
                'icon' => 'crm',
                'path' => '/crm',
                'permission' => 'crm.view',
                'condition_type' => null,
                'order' => 4,
                'is_active' => true,
            ],
            [
                'title' => 'المشاريع',
                'description' => ' ادارة المشاريع',
                'icon' => 'building',
                'path' => '/projects',
                'permission' => 'projects.view',
                'condition_type' => 'has_projects',
                'order' => 5,
                'is_active' => true,
            ],
            [
                'title' => 'العقارات',
                'description' => 'ادارة العقارات',
                'icon' => 'home',
                'path' => '/properties',
                'permission' => 'properties.view',
                'condition_type' => 'has_properties',
                'order' => 6,
                'is_active' => true,
            ],
            [
                'title' => 'طلبات العملاء',
                'description' => 'ادارة طلبات العملاء العقارية',
                'icon' => 'home',
                'path' => '/property-requests',
                'permission' => 'property_requests.view',
                'condition_type' => 'has_properties',
                'order' => 7,
                'is_active' => true,
            ],
            [
                'title' => 'مركز توافق الطلبات الذكائي',
                'description' => 'احصل على توافق ذكي مع الطلبات',
                'icon' => 'sparkles',
                'path' => '/matching',
                'permission' => 'property_requests.view',
                'condition_type' => 'has_properties',
                'order' => 8,
                'is_active' => true,
            ],
            [
                'title' => 'برنامج الشراكة',
                'description' => 'إدارة برنامج العمولة',
                'icon' => 'lucide lucide-user-check h-5 w-5 text-primary',
                'path' => '/affiliate',
                'permission' => 'affiliate.view',
                'condition_type' => 'is_affiliate_approved',
                'order' => 9,
                'is_active' => true,
            ],
            [
                'title' => 'مدير الواتساب',
                'description' => 'اضف ارقام واتساب',
                'icon' => 'message-square-share',
                'path' => '/whatsapp-center',
                'permission' => 'content.view',
                'condition_type' => null,
                'order' => 10,
                'is_active' => true,
            ],
            [
                'title' => 'تعديل تصميم الموقع',
                'description' => 'ادارة محتوى الموقع',
                'icon' => 'content-settings',
                'path' => 'live-editor',
                'permission' => 'content.view',
                'condition_type' => null,
                'order' => 11,
                'is_active' => true,
            ],
            [
                'title' => 'ادارة الموظفين',
                'description' => 'ادارة الموظفين',
                'icon' => 'message-square-share',
                'path' => '/access-control',
                'permission' => 'content.view',
                'condition_type' => null,
                'order' => 12,
                'is_active' => true,
            ],
            [
                'title' => 'ادارة الايجارات',
                'description' => 'ادارة ايجارتك',
                'icon' => 'message-square-share',
                'path' => '/rental-management',
                'permission' => 'rentals.view',
                'condition_type' => null,
                'order' => 13,
                'is_active' => true,
            ],
            [
                'title' => 'المباني',
                'description' => 'ادارة المباني',
                'icon' => 'building',
                'path' => '/buildings',
                'permission' => 'buildings.view',
                'condition_type' => null,
                'order' => 14,
                'is_active' => true,
            ],
            [
                'title' => 'طلبات الوظائف',
                'description' => 'ادارة طلبات الوظائف',
                'icon' => 'briefcase',
                'path' => '/job-applications',
                'permission' => 'job_applications.view',
                'condition_type' => null,
                'order' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($items as $item) {
            ApiSidebarItem::create($item);
        }
    }
}
