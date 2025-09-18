<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateWhatsAppTemplatesTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:create-templates-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create WhatsApp templates table and sample data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Creating WhatsApp templates table...');
            
            // Check if table exists
            $tableExists = DB::select("SHOW TABLES LIKE 'whats_app_templates'");
            
            if (empty($tableExists)) {
                // Create the table
                DB::statement('
                    CREATE TABLE whats_app_templates (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(255) NOT NULL UNIQUE,
                        description TEXT NULL,
                        content TEXT NOT NULL,
                        type VARCHAR(50) NOT NULL,
                        language VARCHAR(10) NOT NULL DEFAULT "ar",
                        variables JSON NULL,
                        character_count INT UNSIGNED NOT NULL DEFAULT 0,
                        status BOOLEAN NOT NULL DEFAULT 1,
                        created_at TIMESTAMP NULL DEFAULT NULL,
                        updated_at TIMESTAMP NULL DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ');
                
                $this->info('Table created successfully!');
            } else {
                $this->info('Table already exists.');
            }
            
            // Create sample templates
            $this->info('Creating sample templates...');
            
            $templates = [
                [
                    'name' => 'welcome_ar_1',
                    'content' => 'مرحباً {name}! شكراً لك على التسجيل في منصتنا. نتمنى لك تجربة ممتعة!',
                    'type' => 'welcome',
                    'language' => 'ar',
                    'description' => 'قالب ترحيب باللغة العربية',
                    'status' => true,
                    'character_count' => 89
                ],
                [
                    'name' => 'subscription_expiry_ar_1',
                    'content' => 'تنبيه: عزيزي {name}، باقة {package_name} ستنتهي في {expiry_date}. يرجى تجديد الباقة لتجنب انقطاع الخدمة.',
                    'type' => 'subscription_expiration',
                    'language' => 'ar',
                    'description' => 'قالب تنبيه انتهاء الباقة باللغة العربية',
                    'status' => true,
                    'character_count' => 120
                ],
                [
                    'name' => 'password_reset_ar_1',
                    'content' => 'مرحباً {name}، رمز إعادة تعيين كلمة المرور: {code}. هذا الرمز صالح لمدة 15 دقيقة.',
                    'type' => 'password_reset',
                    'language' => 'ar',
                    'description' => 'قالب إعادة تعيين كلمة المرور باللغة العربية',
                    'status' => true,
                    'character_count' => 85
                ]
            ];
            
            foreach ($templates as $template) {
                // Check if template already exists
                $exists = DB::table('whats_app_templates')->where('name', $template['name'])->exists();
                
                if (!$exists) {
                    $template['created_at'] = now();
                    $template['updated_at'] = now();
                    DB::table('whats_app_templates')->insert($template);
                    $this->info("Created template: {$template['name']}");
                } else {
                    $this->info("Template already exists: {$template['name']}");
                }
            }
            
            $this->info('WhatsApp templates setup completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
