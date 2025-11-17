<?php

namespace App\Domain\Domain\Models;

use App\Domain\User\Models\User;
use App\Models\Api\ApiDomainSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Custom Domain Model
 * 
 * Represents custom domains for tenant users
 */
class CustomDomain extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\CustomDomainFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_custom_domains';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'requested_domain',
        'current_domain',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * The model's default attribute values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 0,
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'id';
    }

    /**
     * Get the user/tenant that owns this domain.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Linked API domain settings entry (new schema with FK).
     */
    public function apiDomainSetting()
    {
        return $this->hasOne(ApiDomainSetting::class, 'custom_domain_id', 'id');
    }

    /**
     * Scope a query to only include active domains.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include inactive domains.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', false);
    }

    /**
     * Scope a query to only include pending domains (requested but not current).
     */
    public function scopePending($query)
    {
        return $query->whereNotNull('requested_domain')
                    ->whereNull('current_domain');
    }

    /**
     * Scope a query to only include approved domains.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('current_domain');
    }

    /**
     * Scope a query to search domains by domain name or user.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('requested_domain', 'like', "%{$search}%")
              ->orWhere('current_domain', 'like', "%{$search}%")
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('username', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Check if domain is approved.
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return !empty($this->current_domain);
    }

    /**
     * Check if domain is pending.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return !empty($this->requested_domain) && empty($this->current_domain);
    }
}

