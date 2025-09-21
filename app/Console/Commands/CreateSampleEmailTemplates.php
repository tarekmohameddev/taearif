<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EmailTemplate;

class CreateSampleEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:create-sample-templates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample email templates for testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating sample email templates...');

        // Check if table exists and has the right structure
        try {
            $columns = \Schema::getColumnListing('email_templates');
            $this->info('Email templates table columns: ' . implode(', ', $columns));
        } catch (\Exception $e) {
            $this->error('Error checking table structure: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Create sample templates
        $templates = [
            [
                'name' => 'password_reset_ar',
                'description' => 'قالب إعادة تعيين كلمة المرور باللغة العربية',
                'subject' => 'إعادة تعيين كلمة المرور',
                'content' => "مرحباً {name},\n\nتم طلب إعادة تعيين كلمة المرور لحسابك.\n\nرمز إعادة التعيين: {code}\n\nيمكنك أيضاً الضغط على الرابط التالي لإعادة تعيين كلمة المرور:\n{reset_link}\n\nهذا الرمز سينتهي خلال 15 دقيقة.\n\nإذا لم تطلب هذا، يرجى تجاهل هذه الرسالة.\n\nمع أطيب التحيات،\nفريق الدعم",
                'type' => 'password_reset',
                'language' => 'ar',
                'variables' => ['{name}', '{code}', '{reset_link}'],
                'status' => true,
                'character_count' => 0
            ],
            [
                'name' => 'password_reset_en',
                'description' => 'Password reset template in English',
                'subject' => 'Password Reset Request',
                'content' => "Hello {name},\n\nA password reset has been requested for your account.\n\nReset code: {code}\n\nYou can also click the link below to reset your password:\n{reset_link}\n\nThis code will expire in 15 minutes.\n\nIf you did not request this, please ignore this message.\n\nBest regards,\nSupport Team",
                'type' => 'password_reset',
                'language' => 'en',
                'variables' => ['{name}', '{code}', '{reset_link}'],
                'status' => true,
                'character_count' => 0
            ],
            [
                'name' => 'welcome_ar',
                'description' => 'قالب الترحيب باللغة العربية',
                'subject' => 'مرحباً بك في منصتنا',
                'content' => "مرحباً {name},\n\nنرحب بك في منصتنا!\n\nبريدك الإلكتروني: {email}\n\nنشكرك على انضمامك إلينا.\n\nمع أطيب التحيات،\nفريق العمل",
                'type' => 'welcome',
                'language' => 'ar',
                'variables' => ['{name}', '{email}'],
                'status' => true,
                'character_count' => 0
            ],
            [
                'name' => 'subscription_expiration_ar',
                'description' => 'قالب تنبيه انتهاء الاشتراك باللغة العربية',
                'subject' => 'تنبيه: انتهاء الاشتراك قريباً',
                'content' => "مرحباً {name},\n\nنود تذكيرك أن باقتك {package_name} ستنتهي قريباً.\n\nتاريخ الانتهاء: {expiry_date}\n\nيرجى تجديد اشتراكك للاستمرار في الاستفادة من خدماتنا.\n\nمع أطيب التحيات،\nفريق العمل",
                'type' => 'subscription_expiration',
                'language' => 'ar',
                'variables' => ['{name}', '{package_name}', '{expiry_date}'],
                'status' => true,
                'character_count' => 0
            ],
            [
                'name' => 'subscription_expired_ar',
                'description' => 'قالب إشعار انتهاء الاشتراك باللغة العربية',
                'subject' => 'انتهاء الاشتراك',
                'content' => "مرحباً {name},\n\nانتهى اشتراكك وتم نقلك إلى الباقة المجانية.\n\nيمكنك الترقية في أي وقت من لوحة التحكم.\n\nمع أطيب التحيات،\nفريق العمل",
                'type' => 'subscription_expired',
                'language' => 'ar',
                'variables' => ['{name}', '{package_name}', '{expiry_date}'],
                'status' => true,
                'character_count' => 0
            ]
        ];

        foreach ($templates as $templateData) {
            try {
                $templateData['character_count'] = strlen($templateData['content']);
                $templateData['created_at'] = now();
                $templateData['updated_at'] = now();
                
                EmailTemplate::create($templateData);
                $this->info("Created template: {$templateData['name']}");
            } catch (\Exception $e) {
                $this->error("Failed to create template {$templateData['name']}: " . $e->getMessage());
            }
        }

        $this->info('Sample email templates created successfully!');
        return Command::SUCCESS;
    }
}
