<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\User;

class MarketingChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get a sample user to create channels for
        $user = User::first();
        
        if (!$user) {
            $this->command->info('No users found. Please create a user first.');
            return;
        }

        $channels = [
            [
                'user_id' => $user->id,
                'name' => 'Company Main Number',
                'description' => 'Main WhatsApp number for customer support and general inquiries',
                'type' => 'whatsapp',
                'number' => '+966501234567',
                'business_id' => 'BA123456789',
                'phone_id' => 'PN987654321',
                'access_token' => 'sample_access_token_123',
                'is_verified' => true,
                'is_connected' => true,
                'sent_messages_count' => 1250,
                'received_messages_count' => 890,
                'additional_settings' => [
                    'webhook_url' => 'https://example.com/webhook',
                    'template_namespace' => 'company_templates'
                ],
            ],
            [
                'user_id' => $user->id,
                'name' => 'Customer Service Number',
                'description' => 'Dedicated WhatsApp number for customer service inquiries',
                'type' => 'whatsapp',
                'number' => '+966559876543',
                'business_id' => 'BA987654321',
                'phone_id' => 'PN123456789',
                'access_token' => 'sample_access_token_456',
                'is_verified' => false,
                'is_connected' => false,
                'sent_messages_count' => 0,
                'received_messages_count' => 0,
                'additional_settings' => [
                    'webhook_url' => 'https://example.com/customer-service-webhook',
                    'auto_reply_enabled' => true
                ],
            ],
            [
                'user_id' => $user->id,
                'name' => 'Facebook Business Page',
                'description' => 'Facebook Messenger integration for business communications',
                'type' => 'facebook',
                'number' => 'facebook_page_123',
                'business_id' => 'FB123456789',
                'phone_id' => null,
                'access_token' => 'facebook_access_token_789',
                'is_verified' => true,
                'is_connected' => true,
                'sent_messages_count' => 450,
                'received_messages_count' => 320,
                'additional_settings' => [
                    'page_id' => '123456789012345',
                    'app_id' => '987654321098765'
                ],
            ],
        ];

        foreach ($channels as $channelData) {
            MarketingChannel::create($channelData);
        }

        $this->command->info('Marketing channels seeded successfully!');
    }
}
