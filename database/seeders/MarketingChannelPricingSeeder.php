<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Api\markting\MarketingChannelPricing;

class MarketingChannelPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Default price per credit (will be updated from credit packages)
        $defaultPricePerCredit = 0.05; // 0.05 SAR per credit

        $pricingData = [
            [
                'channel_type' => 'whatsapp',
                'credits_per_message' => 1,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 1 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'WhatsApp messaging with media support and templates',
                'description_ar' => 'رسائل واتساب مع دعم الوسائط والقوالب',
                'channel_specific_settings' => [
                    'template_required' => false,
                    'media_support' => true,
                    'webhook_enabled' => true,
                    'max_message_length' => 4096,
                ]
            ],
            [
                'channel_type' => 'facebook',
                'credits_per_message' => 2,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 2 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Facebook Messenger with page integration and post support',
                'description_ar' => 'فيسبوك ماسنجر مع تكامل الصفحات ودعم المنشورات',
                'channel_specific_settings' => [
                    'page_required' => true,
                    'post_support' => true,
                    'messenger_enabled' => true,
                    'max_message_length' => 2000,
                ]
            ],
            [
                'channel_type' => 'telegram',
                'credits_per_message' => 1,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 1 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Telegram bot messaging with inline keyboards and media',
                'description_ar' => 'رسائل بوت تليجرام مع لوحات المفاتيح المدمجة والوسائط',
                'channel_specific_settings' => [
                    'bot_token_required' => true,
                    'group_support' => true,
                    'inline_keyboard' => true,
                    'max_message_length' => 4096,
                ]
            ],
            [
                'channel_type' => 'instagram',
                'credits_per_message' => 3,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 3 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Instagram business messaging with story interactions',
                'description_ar' => 'رسائل إنستجرام للأعمال مع تفاعل القصص',
                'channel_specific_settings' => [
                    'business_account_required' => true,
                    'story_support' => true,
                    'reel_support' => false,
                    'max_message_length' => 1000,
                ]
            ],
            [
                'channel_type' => 'sms',
                'credits_per_message' => 2,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 2 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'SMS messaging with delivery reports and Unicode support',
                'description_ar' => 'رسائل SMS مع تقارير التسليم ودعم اليونيكود',
                'channel_specific_settings' => [
                    'sender_id_required' => true,
                    'unicode_support' => true,
                    'delivery_reports' => true,
                    'max_message_length' => 160,
                ]
            ],
            [
                'channel_type' => 'email',
                'credits_per_message' => 1,
                'price_per_credit' => $defaultPricePerCredit,
                'effective_price_per_message' => 1 * $defaultPricePerCredit,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Email marketing with HTML templates and delivery tracking',
                'description_ar' => 'التسويق عبر البريد الإلكتروني مع قوالب HTML وتتبع التسليم',
                'channel_specific_settings' => [
                    'html_support' => true,
                    'attachments_support' => false,
                    'delivery_reports' => true,
                    'max_recipients_per_campaign' => 10000,
                ]
            ]
        ];

        foreach ($pricingData as $data) {
            MarketingChannelPricing::create($data);
        }
    }
}