<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class PopulatePermissionTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:translate {--force : Force update existing translations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate Arabic and English translations for all permissions';

    /**
     * Translation mappings for permissions
     * 
     * Format: 'permission.name' => ['ar' => 'Arabic Name', 'en' => 'English Name']
     */
    protected $translations = [
        // Properties
        'properties.view' => ['ar' => 'عرض العقارات', 'en' => 'View Properties'],
        'properties.create' => ['ar' => 'إنشاء عقار', 'en' => 'Create Property'],
        'properties.update' => ['ar' => 'تحديث العقارات', 'en' => 'Update Properties'],
        'properties.delete' => ['ar' => 'حذف العقارات', 'en' => 'Delete Properties'],
        'properties.reorder' => ['ar' => 'إعادة ترتيب العقارات', 'en' => 'Reorder Properties'],

        // Customers
        'customers.view' => ['ar' => 'عرض العملاء', 'en' => 'View Customers'],
        'customers.create' => ['ar' => 'إنشاء عميل', 'en' => 'Create Customer'],
        'customers.update' => ['ar' => 'تحديث العملاء', 'en' => 'Update Customers'],
        'customers.delete' => ['ar' => 'حذف العملاء', 'en' => 'Delete Customers'],

        // Projects
        'projects.view' => ['ar' => 'عرض المشاريع', 'en' => 'View Projects'],
        'projects.create' => ['ar' => 'إنشاء مشروع', 'en' => 'Create Project'],
        'projects.update' => ['ar' => 'تحديث المشاريع', 'en' => 'Update Projects'],
        'projects.delete' => ['ar' => 'حذف المشاريع', 'en' => 'Delete Projects'],

        // Settings
        'settings.view' => ['ar' => 'عرض الإعدادات', 'en' => 'View Settings'],
        'settings.update' => ['ar' => 'تحديث الإعدادات', 'en' => 'Update Settings'],

        // Content
        'content.view' => ['ar' => 'عرض المحتوى', 'en' => 'View Content'],
        'content.create' => ['ar' => 'إنشاء محتوى', 'en' => 'Create Content'],
        'content.update' => ['ar' => 'تحديث المحتوى', 'en' => 'Update Content'],
        'content.delete' => ['ar' => 'حذف المحتوى', 'en' => 'Delete Content'],

        // Logs
        'logs.read' => ['ar' => 'قراءة السجلات', 'en' => 'Read Logs'],
        'logs.view' => ['ar' => 'عرض السجلات', 'en' => 'View Logs'],

        // CRM Cards
        'crm.cards.view' => ['ar' => 'عرض بطاقات CRM', 'en' => 'View CRM Cards'],
        'crm.cards.create' => ['ar' => 'إنشاء بطاقة CRM', 'en' => 'Create CRM Card'],
        'crm.cards.update' => ['ar' => 'تحديث بطاقات CRM', 'en' => 'Update CRM Cards'],
        'crm.cards.delete' => ['ar' => 'حذف بطاقات CRM', 'en' => 'Delete CRM Cards'],

        // Rentals
        'rentals.view' => ['ar' => 'عرض الإيجارات', 'en' => 'View Rentals'],
        'rentals.create' => ['ar' => 'إنشاء إيجار', 'en' => 'Create Rental'],
        'rentals.update' => ['ar' => 'تحديث الإيجارات', 'en' => 'Update Rentals'],
        'rentals.delete' => ['ar' => 'حذف الإيجارات', 'en' => 'Delete Rentals'],

        // Contracts
        'contracts.view' => ['ar' => 'عرض العقود', 'en' => 'View Contracts'],
        'contracts.create' => ['ar' => 'إنشاء عقد', 'en' => 'Create Contract'],
        'contracts.update' => ['ar' => 'تحديث العقود', 'en' => 'Update Contracts'],
        'contracts.delete' => ['ar' => 'حذف العقود', 'en' => 'Delete Contracts'],

        // Employees
        'employees.view' => ['ar' => 'عرض الموظفين', 'en' => 'View Employees'],
        'employees.create' => ['ar' => 'إنشاء موظف', 'en' => 'Create Employee'],
        'employees.update' => ['ar' => 'تحديث الموظفين', 'en' => 'Update Employees'],
        'employees.delete' => ['ar' => 'حذف الموظفين', 'en' => 'Delete Employees'],
        'employees.roles.sync' => ['ar' => 'مزامنة أدوار الموظفين', 'en' => 'Sync Employee Roles'],
        'employees.perms.sync' => ['ar' => 'مزامنة صلاحيات الموظفين', 'en' => 'Sync Employee Permissions'],

        // Roles & Permissions
        'roles.view' => ['ar' => 'عرض الأدوار', 'en' => 'View Roles'],
        'roles.create' => ['ar' => 'إنشاء دور', 'en' => 'Create Role'],
        'roles.update' => ['ar' => 'تحديث الأدوار', 'en' => 'Update Roles'],
        'roles.delete' => ['ar' => 'حذف الأدوار', 'en' => 'Delete Roles'],
        'roles.read' => ['ar' => 'قراءة الأدوار', 'en' => 'Read Roles'],
        'roles.write' => ['ar' => 'كتابة الأدوار', 'en' => 'Write Roles'],
        'permissions.read' => ['ar' => 'قراءة الصلاحيات', 'en' => 'Read Permissions'],
        'permissions.write' => ['ar' => 'كتابة الصلاحيات', 'en' => 'Write Permissions'],

        // Buildings
        'buildings.view' => ['ar' => 'عرض المباني', 'en' => 'View Buildings'],
        'buildings.create' => ['ar' => 'إنشاء مبنى', 'en' => 'Create Building'],
        'buildings.update' => ['ar' => 'تحديث المباني', 'en' => 'Update Buildings'],
        'buildings.delete' => ['ar' => 'حذف المباني', 'en' => 'Delete Buildings'],

        // Reports
        'reports.view' => ['ar' => 'عرض التقارير', 'en' => 'View Reports'],
        'reports.create' => ['ar' => 'إنشاء تقرير', 'en' => 'Create Report'],
        'reports.export' => ['ar' => 'تصدير التقارير', 'en' => 'Export Reports'],

        // Payments
        'payments.view' => ['ar' => 'عرض المدفوعات', 'en' => 'View Payments'],
        'payments.create' => ['ar' => 'إنشاء دفعة', 'en' => 'Create Payment'],
        'payments.update' => ['ar' => 'تحديث المدفوعات', 'en' => 'Update Payments'],
        'payments.delete' => ['ar' => 'حذف المدفوعات', 'en' => 'Delete Payments'],

        // Maintenance
        'maintenance.view' => ['ar' => 'عرض الصيانة', 'en' => 'View Maintenance'],
        'maintenance.create' => ['ar' => 'إنشاء طلب صيانة', 'en' => 'Create Maintenance'],
        'maintenance.update' => ['ar' => 'تحديث الصيانة', 'en' => 'Update Maintenance'],
        'maintenance.delete' => ['ar' => 'حذف الصيانة', 'en' => 'Delete Maintenance'],

        // Marketing
        'marketing.view' => ['ar' => 'عرض التسويق', 'en' => 'View Marketing'],
        'marketing.create' => ['ar' => 'إنشاء حملة تسويقية', 'en' => 'Create Marketing'],
        'marketing.update' => ['ar' => 'تحديث التسويق', 'en' => 'Update Marketing'],
        'marketing.delete' => ['ar' => 'حذف التسويق', 'en' => 'Delete Marketing'],

        // CRM
        'crm.view' => ['ar' => 'عرض CRM', 'en' => 'View CRM'],
        'crm.create' => ['ar' => 'إنشاء CRM', 'en' => 'Create CRM'],
        'crm.update' => ['ar' => 'تحديث CRM', 'en' => 'Update CRM'],
        'crm.delete' => ['ar' => 'حذف CRM', 'en' => 'Delete CRM'],

        // Menu Permissions
        'menu.dashboard' => ['ar' => 'قائمة لوحة التحكم', 'en' => 'Dashboard Menu'],
        'menu.content' => ['ar' => 'قائمة المحتوى', 'en' => 'Content Menu'],
        'menu.settings' => ['ar' => 'قائمة الإعدادات', 'en' => 'Settings Menu'],
        'menu.projects' => ['ar' => 'قائمة المشاريع', 'en' => 'Projects Menu'],
        'menu.properties' => ['ar' => 'قائمة العقارات', 'en' => 'Properties Menu'],
        'menu.blog' => ['ar' => 'قائمة المدونة', 'en' => 'Blog Menu'],
        'menu.customers' => ['ar' => 'قائمة العملاء', 'en' => 'Customers Menu'],
        'menu.apps' => ['ar' => 'قائمة التطبيقات', 'en' => 'Apps Menu'],
        'menu.affiliate' => ['ar' => 'قائمة التسويق بالعمولة', 'en' => 'Affiliate Menu'],
        'menu.crm' => ['ar' => 'قائمة CRM', 'en' => 'CRM Menu'],
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting permission translation population...');
        $this->newLine();

        $force = $this->option('force');
        $permissions = Permission::all();
        
        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($permissions as $permission) {
            $permissionName = $permission->name;

            // Check if we have a translation for this permission
            if (!isset($this->translations[$permissionName])) {
                $this->warn("⚠ No translation found for: {$permissionName}");
                $notFound++;
                continue;
            }

            $translation = $this->translations[$permissionName];

            // Skip if already has translations and not forcing
            if (!$force && ($permission->name_ar || $permission->name_en)) {
                $this->line("⏭ Skipped (already has translation): {$permissionName}");
                $skipped++;
                continue;
            }

            // Update the permission
            $permission->update([
                'name_ar' => $translation['ar'],
                'name_en' => $translation['en'],
            ]);

            $this->info("✓ Updated: {$permissionName}");
            $this->line("  AR: {$translation['ar']}");
            $this->line("  EN: {$translation['en']}");
            $updated++;
        }

        $this->newLine();
        $this->info('===== Summary =====');
        $this->info("✓ Updated: {$updated}");
        $this->line("⏭ Skipped: {$skipped}");
        $this->warn("⚠ Not Found: {$notFound}");
        $this->newLine();

        if ($notFound > 0) {
            $this->warn('Add missing translations to the $translations array in:');
            $this->warn('app/Console/Commands/PopulatePermissionTranslations.php');
        }

        return Command::SUCCESS;
    }
}
