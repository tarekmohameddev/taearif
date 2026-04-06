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
            'properties.owner_deed' => [
                'name_en' => 'Manage Owner Details & Deed',
                'name_ar' => 'إدارة تفاصيل المالك والصك',
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

            // Customers Hub — Analytics
            'customers_hub_analytics.view' => [
                'name_en' => 'View Customers Hub Analytics',
                'name_ar' => 'عرض تحليلات مركز العملاء',
            ],
            'customers_hub_analytics.create' => [
                'name_en' => 'Create Customers Hub Analytics',
                'name_ar' => 'إنشاء تحليلات مركز العملاء',
            ],
            'customers_hub_analytics.update' => [
                'name_en' => 'Update Customers Hub Analytics',
                'name_ar' => 'تحديث تحليلات مركز العملاء',
            ],
            'customers_hub_analytics.delete' => [
                'name_en' => 'Delete Customers Hub Analytics',
                'name_ar' => 'حذف تحليلات مركز العملاء',
            ],

            // Customers Hub — Requests
            'customers_hub_requests.view' => [
                'name_en' => 'View Customers Hub Requests',
                'name_ar' => 'عرض طلبات مركز العملاء',
            ],
            'customers_hub_requests.create' => [
                'name_en' => 'Create Customers Hub Requests',
                'name_ar' => 'إنشاء طلبات مركز العملاء',
            ],
            'customers_hub_requests.update' => [
                'name_en' => 'Update Customers Hub Requests',
                'name_ar' => 'تحديث طلبات مركز العملاء',
            ],
            'customers_hub_requests.delete' => [
                'name_en' => 'Delete Customers Hub Requests',
                'name_ar' => 'حذف طلبات مركز العملاء',
            ],

            // Customers Hub — Customers
            'customers_hub_customers.view' => [
                'name_en' => 'View Customers Hub Customers',
                'name_ar' => 'عرض عملاء مركز العملاء',
            ],
            'customers_hub_customers.create' => [
                'name_en' => 'Create Customers Hub Customers',
                'name_ar' => 'إنشاء عملاء مركز العملاء',
            ],
            'customers_hub_customers.update' => [
                'name_en' => 'Update Customers Hub Customers',
                'name_ar' => 'تحديث عملاء مركز العملاء',
            ],
            'customers_hub_customers.delete' => [
                'name_en' => 'Delete Customers Hub Customers',
                'name_ar' => 'حذف عملاء مركز العملاء',
            ],

            // Customers Hub — Pipeline
            'customers_hub_pipeline.view' => [
                'name_en' => 'View Customers Hub Pipeline',
                'name_ar' => 'عرض مسار مركز العملاء',
            ],
            'customers_hub_pipeline.create' => [
                'name_en' => 'Create Customers Hub Pipeline',
                'name_ar' => 'إنشاء مسار مركز العملاء',
            ],
            'customers_hub_pipeline.update' => [
                'name_en' => 'Update Customers Hub Pipeline',
                'name_ar' => 'تحديث مسار مركز العملاء',
            ],
            'customers_hub_pipeline.delete' => [
                'name_en' => 'Delete Customers Hub Pipeline',
                'name_ar' => 'حذف مسار مركز العملاء',
            ],

            // Customers Hub — AI Matching
            'customers_hub_ai_matching.view' => [
                'name_en' => 'View Customers Hub AI Matching',
                'name_ar' => 'عرض المطابقة الذكية في مركز العملاء',
            ],
            'customers_hub_ai_matching.create' => [
                'name_en' => 'Create Customers Hub AI Matching',
                'name_ar' => 'إنشاء المطابقة الذكية في مركز العملاء',
            ],
            'customers_hub_ai_matching.update' => [
                'name_en' => 'Update Customers Hub AI Matching',
                'name_ar' => 'تحديث المطابقة الذكية في مركز العملاء',
            ],
            'customers_hub_ai_matching.delete' => [
                'name_en' => 'Delete Customers Hub AI Matching',
                'name_ar' => 'حذف المطابقة الذكية في مركز العملاء',
            ],

            // Pixels
            'pixels.view' => [
                'name_en' => 'View Pixels',
                'name_ar' => 'عرض البكسلات',
            ],
            'pixels.create' => [
                'name_en' => 'Create Pixel',
                'name_ar' => 'إنشاء بكسل',
            ],
            'pixels.update' => [
                'name_en' => 'Update Pixel',
                'name_ar' => 'تحديث بكسل',
            ],
            'pixels.delete' => [
                'name_en' => 'Delete Pixel',
                'name_ar' => 'حذف بكسل',
            ],

            // WhatsApp Center — Numbers
            'whatsapp_center_numbers.view' => [
                'name_en' => 'View WhatsApp Numbers',
                'name_ar' => 'عرض أرقام واتساب',
            ],
            'whatsapp_center_numbers.create' => [
                'name_en' => 'Create WhatsApp Number',
                'name_ar' => 'إنشاء رقم واتساب',
            ],
            'whatsapp_center_numbers.update' => [
                'name_en' => 'Update WhatsApp Number',
                'name_ar' => 'تحديث رقم واتساب',
            ],
            'whatsapp_center_numbers.delete' => [
                'name_en' => 'Delete WhatsApp Number',
                'name_ar' => 'حذف رقم واتساب',
            ],

            // WhatsApp Center — Campaigns
            'whatsapp_center_campaigns.view' => [
                'name_en' => 'View WhatsApp Campaigns',
                'name_ar' => 'عرض حملات واتساب',
            ],
            'whatsapp_center_campaigns.create' => [
                'name_en' => 'Create WhatsApp Campaign',
                'name_ar' => 'إنشاء حملة واتساب',
            ],
            'whatsapp_center_campaigns.update' => [
                'name_en' => 'Update WhatsApp Campaign',
                'name_ar' => 'تحديث حملة واتساب',
            ],
            'whatsapp_center_campaigns.delete' => [
                'name_en' => 'Delete WhatsApp Campaign',
                'name_ar' => 'حذف حملة واتساب',
            ],

            // WhatsApp Center — Templates
            'whatsapp_center_templates.view' => [
                'name_en' => 'View WhatsApp Templates',
                'name_ar' => 'عرض قوالب واتساب',
            ],
            'whatsapp_center_templates.create' => [
                'name_en' => 'Create WhatsApp Template',
                'name_ar' => 'إنشاء قالب واتساب',
            ],
            'whatsapp_center_templates.update' => [
                'name_en' => 'Update WhatsApp Template',
                'name_ar' => 'تحديث قالب واتساب',
            ],
            'whatsapp_center_templates.delete' => [
                'name_en' => 'Delete WhatsApp Template',
                'name_ar' => 'حذف قالب واتساب',
            ],

            // WhatsApp Chat
            'whatsapp_chat.view' => [
                'name_en' => 'View WhatsApp Chat',
                'name_ar' => 'عرض محادثات واتساب',
            ],
            'whatsapp_chat.create' => [
                'name_en' => 'Send WhatsApp Messages',
                'name_ar' => 'إرسال رسائل واتساب',
            ],

            // SMS Campaigns
            'sms_campaigns.view' => [
                'name_en' => 'View SMS Campaigns',
                'name_ar' => 'عرض حملات الرسائل النصية',
            ],
            'sms_campaigns.create' => [
                'name_en' => 'Create SMS Campaign',
                'name_ar' => 'إنشاء حملة رسائل نصية',
            ],
            'sms_campaigns.update' => [
                'name_en' => 'Update SMS Campaign',
                'name_ar' => 'تحديث حملة رسائل نصية',
            ],
            'sms_campaigns.delete' => [
                'name_en' => 'Delete SMS Campaign',
                'name_ar' => 'حذف حملة رسائل نصية',
            ],

            // Email Campaigns
            'email_campaigns.view' => [
                'name_en' => 'View Email Campaigns',
                'name_ar' => 'عرض حملات البريد الإلكتروني',
            ],
            'email_campaigns.create' => [
                'name_en' => 'Create Email Campaign',
                'name_ar' => 'إنشاء حملة بريد إلكتروني',
            ],
            'email_campaigns.update' => [
                'name_en' => 'Update Email Campaign',
                'name_ar' => 'تحديث حملة بريد إلكتروني',
            ],
            'email_campaigns.delete' => [
                'name_en' => 'Delete Email Campaign',
                'name_ar' => 'حذف حملة بريد إلكتروني',
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
