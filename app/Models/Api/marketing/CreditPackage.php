<?php

namespace App\Models\Api\marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditPackage extends Model
{
    use HasFactory;

    protected $table = 'credit_packages';

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'credits',
        'price',
        'currency',
        'discount_percentage',
        'is_popular',
        'is_active',
        'sort_order',
        'features',
        'supports_marketing_channels',
        'marketing_features',
        'marketing_priority',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
        'supports_marketing_channels' => 'boolean',
        'marketing_features' => 'array',
        'marketing_priority' => 'integer',
    ];

    public function transactions()
    {
        return $this->hasMany(CreditTransaction::class, 'credit_package_id');
    }

    /**
     * Get price per credit
     */
    public function getPricePerCreditAttribute()
    {
        if ($this->credits <= 0) {
            return 0;
        }
        return round($this->price / $this->credits, 4);
    }

    /**
     * Get discounted price
     */
    public function getDiscountedPriceAttribute()
    {
        if ($this->discount_percentage && $this->discount_percentage > 0) {
            $discount = ($this->price * $this->discount_percentage) / 100;
            return round($this->price - $discount, 2);
        }
        return $this->price;
    }

    /**
     * Get savings amount
     */
    public function getSavingsAmountAttribute()
    {
        if ($this->discount_percentage && $this->discount_percentage > 0) {
            return round($this->price - $this->discounted_price, 2);
        }
        return 0;
    }

    /**
     * Check if package has discount
     */
    public function hasDiscount()
    {
        return $this->discount_percentage && $this->discount_percentage > 0;
    }

    /**
     * Check if package supports marketing channels
     */
    public function supportsMarketingChannels()
    {
        return $this->supports_marketing_channels;
    }

    /**
     * Get marketing features
     */
    public function getMarketingFeatures()
    {
        return $this->marketing_features ?? [];
    }

    /**
     * Scope for marketing channel packages
     */
    public function scopeForMarketingChannels($query)
    {
        return $query->where('supports_marketing_channels', true);
    }

    /**
     * Get estimated messages possible for each channel type
     */
    public function getEstimatedMessagesPerChannel()
    {
        $channelPricing = \App\Models\Api\marketing\MarketingChannelPricing::active()->get();
        $estimates = [];

        foreach ($channelPricing as $pricing) {
            $messages = floor($this->credits / $pricing->credits_per_message);
            $estimates[$pricing->channel_type] = [
                'channel_name' => $pricing->channel_type_name,
                'credits_per_message' => $pricing->credits_per_message,
                'estimated_messages' => $messages,
                'remaining_credits' => $this->credits % $pricing->credits_per_message,
            ];
        }

        return $estimates;
    }

    /**
     * Get localized name
     */
    public function getLocalizedName($locale = 'en')
    {
        if ($locale === 'ar' && $this->name_ar) {
            return $this->name_ar;
        }
        return $this->name;
    }

    /**
     * Get localized description
     */
    public function getLocalizedDescription($locale = 'en')
    {
        if ($locale === 'ar' && $this->description_ar) {
            return $this->description_ar;
        }
        return $this->description;
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for popular packages
     */
    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    /**
     * Scope for ordering by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('price', 'asc');
    }

    /**
     * Get all active packages ordered
     */
    public static function getActivePackages()
    {
        return self::active()->ordered()->get();
    }

    /**
     * Get popular package
     */
    public static function getPopularPackage()
    {
        return self::active()->popular()->first();
    }

    /**
     * Calculate savings percentage for display
     */
    public function getDisplaySavingsPercentage()
    {
        if ($this->discount_percentage) {
            return round($this->discount_percentage, 0) . '%';
        }
        return null;
    }

    /**
     * Get package features as array
     */
    public function getPackageFeatures()
    {
        return $this->features ?? [];
    }

    /**
     * Check if package is recommended based on value
     */
    public function isRecommended()
    {
        // A package is recommended if it has the best value (lowest price per credit)
        $bestValuePackage = self::active()->orderByRaw('price / credits ASC')->first();
        return $bestValuePackage && $bestValuePackage->id === $this->id;
    }
}
