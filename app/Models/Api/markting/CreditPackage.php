<?php

namespace App\Models\Api\markting;

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
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'features' => 'array',
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
