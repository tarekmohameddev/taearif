<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $translations = [
            // Dashboard permissions
            'dashboard.view' => [
                'name_en' => 'View Dashboard',
                'name_ar' => 'عرض لوحة التحكم',
            ],
            'dashboard.update' => [
                'name_en' => 'Update Dashboard',
                'name_ar' => 'تحديث لوحة التحكم',
            ],

            // Live Editor permissions
            'live_editor.view' => [
                'name_en' => 'View Live Editor',
                'name_ar' => 'عرض المحرر المباشر',
            ],
            'live_editor.update' => [
                'name_en' => 'Update Live Editor',
                'name_ar' => 'تحديث المحرر المباشر',
            ],

            // Content permissions
            'content.view' => [
                'name_en' => 'View Content',
                'name_ar' => 'عرض المحتوى',
            ],
            'content.create' => [
                'name_en' => 'Create Content',
                'name_ar' => 'إنشاء المحتوى',
            ],
            'content.update' => [
                'name_en' => 'Update Content',
                'name_ar' => 'تحديث المحتوى',
            ],
            'content.delete' => [
                'name_en' => 'Delete Content',
                'name_ar' => 'حذف المحتوى',
            ],

            // Settings permissions
            'settings.view' => [
                'name_en' => 'View Settings',
                'name_ar' => 'عرض الإعدادات',
            ],
            'settings.update' => [
                'name_en' => 'Update Settings',
                'name_ar' => 'تحديث الإعدادات',
            ],

            // Apps permissions
            'apps.view' => [
                'name_en' => 'View Apps',
                'name_ar' => 'عرض التطبيقات',
            ],
            'apps.update' => [
                'name_en' => 'Update Apps',
                'name_ar' => 'تحديث التطبيقات',
            ],

            // Affiliate permissions
            'affiliate.view' => [
                'name_en' => 'View Affiliate',
                'name_ar' => 'عرض التسويق بالعمولة',
            ],
            'affiliate.update' => [
                'name_en' => 'Update Affiliate',
                'name_ar' => 'تحديث التسويق بالعمولة',
            ],

            // Customers permissions
            'customers.view' => [
                'name_en' => 'View Customers',
                'name_ar' => 'عرض العملاء',
            ],
            'customers.create' => [
                'name_en' => 'Create Customer',
                'name_ar' => 'إنشاء عميل',
            ],
            'customers.update' => [
                'name_en' => 'Update Customer',
                'name_ar' => 'تحديث عميل',
            ],
            'customers.delete' => [
                'name_en' => 'Delete Customer',
                'name_ar' => 'حذف عميل',
            ],

            // Projects permissions
            'projects.view' => [
                'name_en' => 'View Projects',
                'name_ar' => 'عرض المشاريع',
            ],
            'projects.create' => [
                'name_en' => 'Create Project',
                'name_ar' => 'إنشاء مشروع',
            ],
            'projects.update' => [
                'name_en' => 'Update Project',
                'name_ar' => 'تحديث مشروع',
            ],
            'projects.delete' => [
                'name_en' => 'Delete Project',
                'name_ar' => 'حذف مشروع',
            ],

            // Properties permissions
            'properties.view' => [
                'name_en' => 'View Properties',
                'name_ar' => 'عرض العقارات',
            ],
            'properties.create' => [
                'name_en' => 'Create Property',
                'name_ar' => 'إنشاء عقار',
            ],
            'properties.update' => [
                'name_en' => 'Update Property',
                'name_ar' => 'تحديث عقار',
            ],
            'properties.delete' => [
                'name_en' => 'Delete Property',
                'name_ar' => 'حذف عقار',
            ],

            // CRM permissions
            'crm.view' => [
                'name_en' => 'View CRM',
                'name_ar' => 'عرض إدارة العملاء',
            ],
            'crm.create' => [
                'name_en' => 'Create CRM Entry',
                'name_ar' => 'إنشاء سجل إدارة العملاء',
            ],
            'crm.update' => [
                'name_en' => 'Update CRM Entry',
                'name_ar' => 'تحديث سجل إدارة العملاء',
            ],
            'crm.delete' => [
                'name_en' => 'Delete CRM Entry',
                'name_ar' => 'حذف سجل إدارة العملاء',
            ],

        ];

        // Update permissions with translations
        foreach ($translations as $permissionName => $translation) {
            DB::table('api_permissions')
                ->where('name', $permissionName)
                ->update([
                    'name_en' => $translation['name_en'],
                    'name_ar' => $translation['name_ar'],
                ]);
        }

        $this->command->info('Permission translations have been populated successfully!');
        $this->command->info('Total permissions updated: ' . count($translations));
    }
}

