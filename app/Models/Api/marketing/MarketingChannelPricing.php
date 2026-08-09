<?php

namespace App\Models\Api\marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingChannelPricing extends Model
{
    use HasFactory;

    protected $table = 'marketing_channel_pricing';

    protected $fillable = [
        'channel_type',
        'message_category',
        'credits_per_message',
        'price_per_credit',
        'effective_price_per_message',
        'currency',
        'is_active',
        'is_billable',
        'description',
        'description_ar',
        'label_ar',
        'channel_specific_settings',
    ];

    protected $casts = [
        'credits_per_message' => 'integer',
        'price_per_credit' => 'decimal:4',
        'effective_price_per_message' => 'decimal:4',
        'is_active' => 'boolean',
        'is_billable' => 'boolean',
        'channel_specific_settings' => 'array',
    ];

    // Channel types constants
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_FACEBOOK = 'facebook';
    const CHANNEL_TELEGRAM = 'telegram';
    const CHANNEL_INSTAGRAM = 'instagram';
    const CHANNEL_SMS = 'sms';

    // Message category constants (WhatsApp-specific)
    const CATEGORY_MARKETING       = 'marketing';
    const CATEGORY_UTILITY         = 'utility';
    const CATEGORY_AUTHENTICATION  = 'authentication';
    const CATEGORY_AI_BOT          = 'ai_bot';
    const CATEGORY_SERVICE         = 'service';
    const CATEGORY_DEFAULT         = 'default';

    public static function getChannelTypes()
    {
        return [
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_FACEBOOK => 'Facebook',
            self::CHANNEL_TELEGRAM => 'Telegram',
            self::CHANNEL_INSTAGRAM => 'Instagram',
            self::CHANNEL_SMS => 'SMS',
        ];
    }

    /**
     * All known message categories with Arabic labels.
     */
    public static function getMessageCategories()
    {
        return [
            self::CATEGORY_MARKETING      => ['en' => 'Marketing',       'ar' => 'تسويقية'],
            self::CATEGORY_UTILITY        => ['en' => 'Utility',         'ar' => 'خدمات'],
            self::CATEGORY_AUTHENTICATION => ['en' => 'Authentication',  'ar' => 'مصادقة'],
            self::CATEGORY_AI_BOT         => ['en' => 'AI Bot',          'ar' => 'بوت ذكي'],
            self::CATEGORY_SERVICE        => ['en' => 'Service (Free)',  'ar' => 'خدمة (مجاني)'],
            self::CATEGORY_DEFAULT        => ['en' => 'Default',         'ar' => 'افتراضي'],
        ];
    }

    /**
     * Resolve the pricing row for a given channel+category, falling back to 'default'.
     * Returns null only if neither exact nor default row exists.
     */
    public static function resolveFor(string $channelType, string $category): ?self
    {
        // Try exact match first
        $exact = self::active()
            ->forChannel($channelType)
            ->forCategory($category)
            ->first();

        if ($exact !== null) {
            return $exact;
        }

        // Fall back to default category
        if ($category !== self::CATEGORY_DEFAULT) {
            return self::active()
                ->forChannel($channelType)
                ->forCategory(self::CATEGORY_DEFAULT)
                ->first();
        }

        return null;
    }

    /**
     * Get channel type name
     */
    public function getChannelTypeNameAttribute()
    {
        return self::getChannelTypes()[$this->channel_type] ?? $this->channel_type;
    }

    /**
     * Calculate effective price per message
     */
    public function calculateEffectivePrice()
    {
        return round($this->credits_per_message * $this->price_per_credit, 4);
    }

    /**
     * Update effective price when credits or price changes
     */
    public function updateEffectivePrice()
    {
        $this->effective_price_per_message = $this->calculateEffectivePrice();
        $this->save();
    }

    /**
     * Get total cost for a specific number of messages
     */
    public function getTotalCostForMessages($messageCount)
    {
        return round($this->effective_price_per_message * $messageCount, 2);
    }

    /**
     * Get total credits required for a specific number of messages
     */
    public function getTotalCreditsForMessages($messageCount)
    {
        return $this->credits_per_message * $messageCount;
    }

    /**
     * Check if channel pricing is available
     */
    public function isAvailable()
    {
        return $this->is_active;
    }

    /**
     * Scope for active pricing
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific channel type
     */
    public function scopeForChannel($query, $channelType)
    {
        return $query->where('channel_type', $channelType);
    }

    /**
     * Scope for specific message category
     */
    public function scopeForCategory($query, $category)
    {
        return $query->where('message_category', $category);
    }

    /**
     * Get default channel-specific settings
     */
    public function getDefaultChannelSettings()
    {
        $defaults = [
            self::CHANNEL_WHATSAPP => [
                'template_required' => true,
                'media_support' => true,
                'webhook_enabled' => true,
                'max_message_length' => 4096,
            ],
            self::CHANNEL_FACEBOOK => [
                'page_required' => true,
                'post_support' => true,
                'messenger_enabled' => true,
                'max_message_length' => 2000,
            ],
            self::CHANNEL_TELEGRAM => [
                'bot_token_required' => true,
                'group_support' => true,
                'inline_keyboard' => true,
                'max_message_length' => 4096,
            ],
            self::CHANNEL_INSTAGRAM => [
                'business_account_required' => true,
                'story_support' => true,
                'reel_support' => false,
                'max_message_length' => 1000,
            ],
            self::CHANNEL_SMS => [
                'sender_id_required' => true,
                'unicode_support' => true,
                'delivery_reports' => true,
                'max_message_length' => 160,
            ],
        ];

        return $defaults[$this->channel_type] ?? [];
    }

    /**
     * Get merged channel settings
     */
    public function getMergedChannelSettings()
    {
        $defaults = $this->getDefaultChannelSettings();
        $custom = $this->channel_specific_settings ?? [];
        
        return array_merge($defaults, $custom);
    }

    /**
     * Get pricing comparison across all channels
     */
    public static function getPricingComparison()
    {
        return self::active()
            ->orderBy('effective_price_per_message')
            ->get()
            ->map(function ($pricing) {
                return [
                    'channel_type' => $pricing->channel_type,
                    'channel_name' => $pricing->channel_type_name,
                    'credits_per_message' => $pricing->credits_per_message,
                    'price_per_message' => $pricing->effective_price_per_message,
                    'currency' => $pricing->currency,
                ];
            });
    }

    /**
     * Update price per credit from credit packages
     */
    public function updatePriceFromCreditPackages()
    {
        // Get the average price per credit from active credit packages
        $avgPricePerCredit = \App\Models\Api\marketing\CreditPackage::active()
            ->where('supports_marketing_channels', true)
            ->avg('price_per_credit');

        if ($avgPricePerCredit) {
            $this->price_per_credit = round($avgPricePerCredit, 4);
            $this->updateEffectivePrice();
        }
    }
}