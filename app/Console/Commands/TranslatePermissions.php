<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class TranslatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:translate {--force : Force update even if translations exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate English and Arabic translations for permissions';

    /**
     * Arabic translations for common terms
     */
    protected $arabicTranslations = [
        // Actions
        'view' => 'عرض',
        'read' => 'قراءة',
        'write' => 'كتابة',
        'create' => 'إنشاء',
        'edit' => 'تعديل',
        'update' => 'تحديث',
        'delete' => 'حذف',
        'export' => 'تصدير',
        'import' => 'استيراد',
        'manage' => 'إدارة',
        'access' => 'الوصول',
        'list' => 'قائمة',
        'show' => 'عرض',
        'store' => 'حفظ',
        'destroy' => 'إزالة',
        'restore' => 'استعادة',
        'assign' => 'تعيين',
        'remove' => 'إزالة',
        'attach' => 'إرفاق',
        'detach' => 'فصل',
        'sync' => 'مزامنة',
        'approve' => 'موافقة',
        'reject' => 'رفض',
        'publish' => 'نشر',
        'unpublish' => 'إلغاء النشر',
        'download' => 'تحميل',
        'upload' => 'رفع',
        'print' => 'طباعة',
        'search' => 'بحث',
        'filter' => 'تصفية',
        
        // Modules
        'analytics' => 'التحليلات',
        'dashboard' => 'لوحة التحكم',
        'users' => 'المستخدمين',
        'user' => 'مستخدم',
        'roles' => 'الأدوار',
        'role' => 'دور',
        'permissions' => 'الصلاحيات',
        'permission' => 'صلاحية',
        'employees' => 'الموظفين',
        'employee' => 'موظف',
        'customers' => 'العملاء',
        'customer' => 'عميل',
        'properties' => 'العقارات',
        'property' => 'عقار',
        'projects' => 'المشاريع',
        'project' => 'مشروع',
        'contracts' => 'العقود',
        'contract' => 'عقد',
        'rentals' => 'الإيجارات',
        'rental' => 'إيجار',
        'sales' => 'المبيعات',
        'sale' => 'بيع',
        'payments' => 'المدفوعات',
        'payment' => 'دفعة',
        'invoices' => 'الفواتير',
        'invoice' => 'فاتورة',
        'reports' => 'التقارير',
        'report' => 'تقرير',
        'settings' => 'الإعدادات',
        'setting' => 'إعداد',
        'logs' => 'السجلات',
        'log' => 'سجل',
        'cards' => 'البطاقات',
        'card' => 'بطاقة',
        'crm' => 'إدارة علاقات العملاء',
        'rms' => 'نظام إدارة الإيجارات',
        'cms' => 'نظام إدارة المحتوى',
        'maintenance' => 'الصيانة',
        'expenses' => 'المصروفات',
        'expense' => 'مصروف',
        'installments' => 'الأقساط',
        'installment' => 'قسط',
        'reminders' => 'التذكيرات',
        'reminder' => 'تذكير',
        'categories' => 'الفئات',
        'category' => 'فئة',
        'types' => 'الأنواع',
        'type' => 'نوع',
        'stages' => 'المراحل',
        'stage' => 'مرحلة',
        'priorities' => 'الأولويات',
        'priority' => 'أولوية',
        'procedures' => 'الإجراءات',
        'procedure' => 'إجراء',
        'appointments' => 'المواعيد',
        'appointment' => 'موعد',
        'inquiries' => 'الاستفسارات',
        'inquiry' => 'استفسار',
        'notifications' => 'الإشعارات',
        'notification' => 'إشعار',
        'messages' => 'الرسائل',
        'message' => 'رسالة',
        'website' => 'الموقع الإلكتروني',
        'pages' => 'الصفحات',
        'page' => 'صفحة',
        'media' => 'الوسائط',
        'forms' => 'النماذج',
        'form' => 'نموذج',
        'components' => 'المكونات',
        'component' => 'مكون',
        'globals' => 'العامة',
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting permission translation...');
        
        $force = $this->option('force');
        
        // Bypass team scoping to get all permissions
        $permissions = \DB::table(config('permission.table_names.permissions'))->get();
        
        if ($permissions->isEmpty()) {
            $this->warn('No permissions found in the database.');
            return Command::FAILURE;
        }
        
        $this->info("Found {$permissions->count()} permissions.");
        
        $bar = $this->output->createProgressBar($permissions->count());
        $bar->start();
        
        $updated = 0;
        $skipped = 0;
        
        foreach ($permissions as $permission) {
            // Skip if translations exist and not forcing
            if (!$force && $permission->name_en && $permission->name_ar) {
                $skipped++;
                $bar->advance();
                continue;
            }
            
            // Generate translations
            $nameEn = $force || !$permission->name_en 
                ? $this->generateEnglishName($permission->name) 
                : $permission->name_en;
                
            $nameAr = $force || !$permission->name_ar 
                ? $this->generateArabicName($permission->name) 
                : $permission->name_ar;
            
            // Update directly in database
            \DB::table(config('permission.table_names.permissions'))
                ->where('id', $permission->id)
                ->update([
                    'name_en' => $nameEn,
                    'name_ar' => $nameAr,
                ]);
            
            $updated++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Translation completed!");
        $this->info("Updated: {$updated} permissions");
        
        if ($skipped > 0) {
            $this->info("Skipped: {$skipped} permissions (already translated)");
            $this->comment("Use --force flag to update existing translations");
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info("Permission cache cleared.");
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate English name from permission name
     */
    protected function generateEnglishName($name)
    {
        // Split by dot and convert to title case
        $parts = explode('.', $name);
        $parts = array_map(function($part) {
            return Str::title(str_replace(['-', '_'], ' ', $part));
        }, $parts);
        
        return implode(' - ', $parts);
    }
    
    /**
     * Generate Arabic name from permission name
     */
    protected function generateArabicName($name)
    {
        // Split by dot
        $parts = explode('.', $name);
        $arabicParts = [];
        
        foreach ($parts as $part) {
            // Split by dash or underscore
            $words = preg_split('/[-_]/', $part);
            $translatedWords = [];
            
            foreach ($words as $word) {
                $word = strtolower($word);
                $translatedWords[] = $this->arabicTranslations[$word] ?? Str::title($word);
            }
            
            $arabicParts[] = implode(' ', $translatedWords);
        }
        
        return implode(' - ', $arabicParts);
    }
}
