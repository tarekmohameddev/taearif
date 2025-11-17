<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Membership;

/**
 * Plan Model
 *
 * Represents subscription plans (packages table)
 * Alias for Package model
 */
class Plan extends Model
{
    use HasFactory;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PlanFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'icon',
        'subtitle',
        'slug',
        'price',
        'term',
        'featured',
        'is_trial',
        'trial_days',
        'status',
        'is_active',
        'new_features',
        'features',
        'meta_keywords',
        'meta_description',
        'number_of_vcards',
        'project_limit_number',
        'real_estate_limit_number',
        'video_size_limit',
        'file_size_limit',
        'serial_number',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'new_features' => 'array',
        'number_of_vcards' => 'integer',
        'project_limit_number' => 'integer',
        'real_estate_limit_number' => 'integer',
        'video_size_limit' => 'integer',
        'file_size_limit' => 'integer',
        'serial_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the memberships for the plan.
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class, 'package_id');
    }

    /**
     * Get subscribers count
     *
     * @return int
     */
    public function getSubscribersCountAttribute(): int
    {
        return $this->memberships()
            ->where('status', 1)
            ->where('expire_date', '>=', now())
            ->count();
    }

    /**
     * Check if plan is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active === true || $this->is_active === 1;
    }

    /**
     * Check if plan is featured
     *
     * @return bool
     */
    public function isFeatured(): bool
    {
        return $this->featured === true;
    }

    /**
     * Check if plan has trial
     *
     * @return bool
     */
    public function hasTrial(): bool
    {
        return $this->is_trial === true && $this->trial_days > 0;
    }

    /**
     * Scope a query to only include active plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', '1');
    }

    /**
     * Scope a query to search plans.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $term
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('subtitle', 'like', "%{$term}%")
              ->orWhere('slug', 'like', "%{$term}%");
        });
    }

    /**
     * Accessor & mutator for featured flag stored as enum('0','1').
     */
    protected function featured(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === '1' || $value === 1 || $value === true,
            set: fn ($value) => $this->convertToEnumFlag($value)
        );
    }

    /**
     * Accessor & mutator for trial flag stored as enum('0','1').
     */
    protected function isTrial(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === '1' || $value === 1 || $value === true,
            set: fn ($value) => $this->convertToEnumFlag($value)
        );
    }

    /**
     * Mutator for status flag stored as enum('0','1').
     */
    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = $this->convertToEnumFlag($value);
    }

    /**
     * Mutator for is_active flag when backed by enum('0','1').
     */
    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = $this->convertToEnumFlag($value);
    }

    /**
     * Normalize boolean-like values to legacy enum strings.
     */
    protected function convertToEnumFlag(mixed $value): string
    {
        if (is_string($value)) {
            $value = strtolower($value);
            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                return '1';
            }
            if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
                return '0';
            }
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? '1' : '0';
        }

        return '0';
    }
}

