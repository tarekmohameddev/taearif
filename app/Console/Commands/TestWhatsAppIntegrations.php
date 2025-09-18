<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\BasicSetting;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestWhatsAppIntegrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:test-integrations {--phone=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test WhatsApp welcome and subscription expiration integrations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $phone = $this->option('phone');
        
        if (!$phone) {
            $phone = $this->ask('Enter phone number for testing (with country code, e.g., +966501234567)');
        }

        if (!$phone) {
            $this->error('Phone number is required for testing.');
            return self::FAILURE;
        }

        $this->info("Testing WhatsApp integrations with phone: {$phone}");
        
        try {
            $whatsappService = new WhatsAppService();
            $bs = BasicSetting::first();
            
            if (!$bs) {
                $this->error('Basic settings not found. Please configure the system first.');
                return self::FAILURE;
            }

            // Test 1: Welcome Message
            $this->info('Testing welcome message...');
            if ($bs->welcome_message_enabled && !empty($bs->welcome_message_text)) {
                $testMessage = str_replace('{name}', 'Test User', $bs->welcome_message_text);
                $testMessage = str_replace('{email}', 'test@example.com', $testMessage);
                
                $whatsappService->sendWelcomeMessage($phone, $testMessage, 'Test User');
                $this->info('✅ Welcome message test sent successfully');
            } else {
                $this->warn('⚠️  Welcome message is disabled or not configured');
            }

            // Test 2: Subscription Expiration Message
            $this->info('Testing subscription expiration message...');
            if ($bs->subscription_expiration_enabled && !empty($bs->subscription_expiration_text)) {
                $testMessage = str_replace('{name}', 'Test User', $bs->subscription_expiration_text);
                $testMessage = str_replace('{package_name}', 'Test Package', $testMessage);
                $testMessage = str_replace('{expiry_date}', '2024-12-31', $testMessage);
                
                $whatsappService->sendSubscriptionExpirationMessage($phone, $testMessage, 'Test User', 'Test Package', '2024-12-31');
                $this->info('✅ Subscription expiration message test sent successfully');
            } else {
                $this->warn('⚠️  Subscription expiration message is disabled or not configured');
            }

            $this->info('🎉 All tests completed! Check your WhatsApp for the test messages.');
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            Log::error('WhatsApp integration test failed', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return self::FAILURE;
        }
    }
}
