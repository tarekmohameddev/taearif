<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use App\Models\BasicExtended;
use App\Models\EmailTemplate;

class TestEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-notifications {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all email notification functionality';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');
            return Command::FAILURE;
        }

        $this->info("Testing email notifications for: {$email}");
        $this->newLine();

        $emailService = new EmailService();
        $be = BasicExtended::first();

        // Test 1: Welcome Email
        $this->info('1. Testing Welcome Email...');
        try {
            $templateName = $be ? $be->welcome_message_template : null;
            $success = $emailService->sendWelcomeEmail($email, 'Test User', 'ar', $templateName);
            
            if ($success) {
                $this->info('   ✅ Welcome email sent successfully');
            } else {
                $this->error('   ❌ Welcome email failed');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Welcome email error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 2: Subscription Expiration Email
        $this->info('2. Testing Subscription Expiration Email...');
        try {
            $templateName = $be ? $be->subscription_expiration_template : null;
            $success = $emailService->sendSubscriptionExpirationEmail(
                $email, 
                'Test User', 
                'الباقة المميزة', 
                '2024-12-31', 
                'ar', 
                $templateName
            );
            
            if ($success) {
                $this->info('   ✅ Subscription expiration email sent successfully');
            } else {
                $this->error('   ❌ Subscription expiration email failed');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Subscription expiration email error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 3: Subscription Expired Email
        $this->info('3. Testing Subscription Expired Email...');
        try {
            $templateName = $be ? $be->subscription_expired_template : null;
            $success = $emailService->sendSubscriptionExpiredEmail(
                $email, 
                'Test User', 
                'الباقة المميزة', 
                '2024-12-31', 
                'ar', 
                $templateName
            );
            
            if ($success) {
                $this->info('   ✅ Subscription expired email sent successfully');
            } else {
                $this->error('   ❌ Subscription expired email failed');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Subscription expired email error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 4: Password Reset Email
        $this->info('4. Testing Password Reset Email...');
        try {
            $templateName = $be ? $be->email_password_reset_template : null;
            $success = $emailService->sendPasswordResetCode(
                $email, 
                'Test User', 
                '123456', 
                'ar', 
                $templateName,
                'https://example.com/reset'
            );
            
            if ($success) {
                $this->info('   ✅ Password reset email sent successfully');
            } else {
                $this->error('   ❌ Password reset email failed');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Password reset email error: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 5: List Available Templates
        $this->info('5. Available Email Templates:');
        try {
            $templates = EmailTemplate::active()->get();
            if ($templates->count() > 0) {
                $this->table(
                    ['Name', 'Type', 'Language', 'Status'],
                    $templates->map(function ($template) {
                        return [
                            $template->name,
                            $template->type,
                            $template->language,
                            $template->status ? 'Active' : 'Inactive'
                        ];
                    })->toArray()
                );
            } else {
                $this->warn('   No email templates found');
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Error listing templates: ' . $e->getMessage());
        }
        $this->newLine();

        // Test 6: Email Notification Settings
        $this->info('6. Email Notification Settings:');
        if ($be) {
            $this->table(
                ['Setting', 'Value'],
                [
                    ['Welcome Email Enabled', $be->welcome_message_email_enabled ? 'Yes' : 'No'],
                    ['Subscription Expiration Email Enabled', $be->subscription_expiration_email_enabled ? 'Yes' : 'No'],
                    ['Subscription Expired Email Enabled', $be->subscription_expired_email_enabled ? 'Yes' : 'No'],
                    ['Welcome Template', $be->welcome_message_template ?? 'None'],
                    ['Subscription Expiration Template', $be->subscription_expiration_template ?? 'None'],
                    ['Subscription Expired Template', $be->subscription_expired_template ?? 'None'],
                    ['Password Reset Template', $be->email_password_reset_template ?? 'None'],
                ]
            );
        } else {
            $this->warn('   No email settings found');
        }

        $this->newLine();
        $this->info('Email notification testing completed!');
        
        return Command::SUCCESS;
    }
}