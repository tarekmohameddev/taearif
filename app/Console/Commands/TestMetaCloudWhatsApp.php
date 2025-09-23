<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use App\Models\BasicSetting;
use Illuminate\Console\Command;

class TestMetaCloudWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-meta {phone} {--all : Test all message types} {--welcome : Test welcome message} {--expiration : Test subscription expiration} {--expired : Test subscription expired} {--password : Test password reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp messages using Meta Cloud API';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        
        // Switch to Meta Cloud API
        $settings = BasicSetting::first();
        $originalService = $settings->whatsapp_service;
        $settings->whatsapp_service = 'meta_cloud';
        $settings->save();
        
        $this->info("🚀 Testing WhatsApp messages with Meta Cloud API for phone: {$phone}");
        $this->info("📱 Switched to Meta Cloud API");
        
        $whatsappService = new WhatsAppService();
        $testResults = [];
        
        // Test welcome message
        if ($this->option('all') || $this->option('welcome')) {
            $this->info("\n1️⃣ Testing Welcome Message...");
            try {
                $testMessage = "مرحباً بك في منصتنا! هذا اختبار لرسالة الترحيب.";
                $result = $whatsappService->sendWelcomeMessage($phone, $testMessage, 'مستخدم تجريبي');
                $testResults['welcome'] = $result;
                $this->info($result ? "✅ Welcome message sent successfully" : "❌ Welcome message failed");
            } catch (\Exception $e) {
                $this->error("❌ Welcome message error: " . $e->getMessage());
                $testResults['welcome'] = false;
            }
        }
        
        // Test subscription expiration
        if ($this->option('all') || $this->option('expiration')) {
            $this->info("\n2️⃣ Testing Subscription Expiration...");
            try {
                $testMessage = "{name}، تنبيه: اشتراكك في {package_name} سينتهي في {expiry_date}. يرجى تجديد اشتراكك لتجنب انقطاع الخدمة.";
                $result = $whatsappService->sendSubscriptionExpirationMessage($phone, $testMessage, 'مستخدم تجريبي', 'الباقة الذهبية', '2024-12-31');
                $testResults['expiration'] = $result;
                $this->info($result ? "✅ Subscription expiration sent successfully" : "❌ Subscription expiration failed");
            } catch (\Exception $e) {
                $this->error("❌ Subscription expiration error: " . $e->getMessage());
                $testResults['expiration'] = false;
            }
        }
        
        // Test subscription expired
        if ($this->option('all') || $this->option('expired')) {
            $this->info("\n3️⃣ Testing Subscription Expired...");
            try {
                $testMessage = "{name}، انتهت صلاحية اشتراكك في {package_name} في {expiry_date}. يرجى تجديد اشتراكك لاستعادة الخدمة.";
                $result = $whatsappService->sendSubscriptionExpiredMessage($phone, $testMessage, 'مستخدم تجريبي', 'الباقة الذهبية', '2024-12-31');
                $testResults['expired'] = $result;
                $this->info($result ? "✅ Subscription expired sent successfully" : "❌ Subscription expired failed");
            } catch (\Exception $e) {
                $this->error("❌ Subscription expired error: " . $e->getMessage());
                $testResults['expired'] = false;
            }
        }
        
        // Test password reset
        if ($this->option('all') || $this->option('password')) {
            $this->info("\n4️⃣ Testing Password Reset...");
            try {
                $testCode = rand(100000, 999999);
                $resetUrl = env('FRONTEND_URL', 'https://app.taearif.com') . '/reset';
                $result = $whatsappService->sendPasswordResetCode($phone, $testCode, 'مستخدم تجريبي', 'ar', $resetUrl, 'password_reset');
                $testResults['password'] = $result;
                $this->info($result ? "✅ Password reset sent successfully (Code: {$testCode})" : "❌ Password reset failed");
            } catch (\Exception $e) {
                $this->error("❌ Password reset error: " . $e->getMessage());
                $testResults['password'] = false;
            }
        }
        
        // Show summary
        $this->info("\n📊 Test Summary:");
        $successCount = 0;
        $totalTests = count($testResults);
        
        foreach ($testResults as $type => $result) {
            $status = $result ? "✅ PASS" : "❌ FAIL";
            $this->info("   {$type}: {$status}");
            if ($result) $successCount++;
        }
        
        $this->info("\n🎯 Results: {$successCount}/{$totalTests} tests passed");
        
        // Restore original service
        $settings->whatsapp_service = $originalService;
        $settings->save();
        $this->info("🔄 Restored original WhatsApp service: {$originalService}");
        
        return $successCount === $totalTests ? 0 : 1;
    }
}