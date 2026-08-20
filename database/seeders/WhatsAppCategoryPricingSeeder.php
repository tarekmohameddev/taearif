<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Api\marketing\MarketingChannelPricing;

class WhatsAppCategoryPricingSeeder extends Seeder
{
    /**
     * Seed per-category WhatsApp pricing rows using updateOrCreate so this
     * seeder is idempotent and safe to re-run.
     *
     * Credits are expressed on the 10x scale (post-Migration B):
     *   Marketing = 10, Utility = 2, Authentication = 2, AI Bot = 1, Service = 0 (free)
     *
     * price_per_credit is the default 0.005 SAR (= 0.05 SAR / 10 after rescale).
     * effective_price_per_message = credits_per_message × price_per_credit.
     */
    public function run()
    {
        $defaultPricePerCredit = 0.005; // SAR (rescaled: was 0.05 on the 1-credit scale)

        $categories = [
            [
                'message_category'          => MarketingChannelPricing::CATEGORY_MARKETING,
                'credits_per_message'        => 10,
                'is_billable'               => true,
                'description'               => 'WhatsApp Marketing template — always charged by Meta.',
                'description_ar'            => 'رسائل واتساب التسويقية — تُحسب دائماً من الرصيد.',
                'label_ar'                  => 'تسويقية',
            ],
            [
                'message_category'          => MarketingChannelPricing::CATEGORY_UTILITY,
                'credits_per_message'        => 2,
                'is_billable'               => true,
                'description'               => 'WhatsApp Utility template — charged when outside the 24h service window.',
                'description_ar'            => 'رسائل واتساب الخدمية — تُحسب خارج نافذة الخدمة 24 ساعة.',
                'label_ar'                  => 'خدمات',
            ],
            [
                'message_category'          => MarketingChannelPricing::CATEGORY_AUTHENTICATION,
                'credits_per_message'        => 2,
                'is_billable'               => true,
                'description'               => 'WhatsApp Authentication template (OTP/verification).',
                'description_ar'            => 'رسائل واتساب للمصادقة وكلمات المرور لمرة واحدة.',
                'label_ar'                  => 'مصادقة',
            ],
            [
                'message_category'          => MarketingChannelPricing::CATEGORY_AI_BOT,
                'credits_per_message'        => 1,
                'is_billable'               => true,
                'description'               => 'AI Bot reply — covers LLM inference cost (Meta charges $0 for service messages).',
                'description_ar'            => 'ردود البوت الذكي — يغطي تكلفة معالجة اللغة (واتساب لا يتقاضى شيئاً على رسائل الخدمة).',
                'label_ar'                  => 'بوت ذكي',
            ],
            [
                'message_category'          => MarketingChannelPricing::CATEGORY_SERVICE,
                'credits_per_message'        => 0,
                'is_billable'               => false,
                'description'               => 'WhatsApp Service messages inside the 24h customer service window — free from Meta.',
                'description_ar'            => 'رسائل الخدمة داخل نافذة 24 ساعة — مجانية من ميتا.',
                'label_ar'                  => 'خدمة (مجاني)',
            ],
        ];

        foreach ($categories as $data) {
            MarketingChannelPricing::updateOrCreate(
                [
                    'channel_type'     => 'whatsapp',
                    'message_category' => $data['message_category'],
                ],
                array_merge($data, [
                    'channel_type'             => 'whatsapp',
                    'price_per_credit'         => $defaultPricePerCredit,
                    'effective_price_per_message' => $data['credits_per_message'] * $defaultPricePerCredit,
                    'currency'                 => 'SAR',
                    'is_active'                => true,
                    'channel_specific_settings' => [
                        'template_required' => true,
                        'media_support'     => true,
                        'webhook_enabled'   => true,
                        'max_message_length' => 4096,
                    ],
                ])
            );
        }
    }
}
