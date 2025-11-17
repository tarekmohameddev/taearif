<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Membership;
use App\Models\Api\GeneralSetting;
use Database\Factories\UserFactory;

/**
 * User Model (Tenant)
 *
 * Represents tenant users in the system
 * Uses Sanctum for API authentication
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'referred_by',
        'account_type',
        'active',
        'first_name',
        'last_name',
        'photo',
        'username',
        'email',
        'password',
        'company_name',
        'phone',
        'city',
        'state',
        'address',
        'country',
        'status',
        'online_status',
        'email_verified',
        'featured',
        'subscribed',
        'subscription_amount',
        'referral_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'status' => 'integer',
        'featured' => 'integer',
        'online_status' => 'boolean',
        'email_verified' => 'boolean',
        'subscribed' => 'boolean',
        'subscription_amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    protected $dates = ['deleted_at'];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * Get user's full name
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the user that referred this user.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    /**
     * Get the users referred by this user.
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    /**
     * Get all memberships for the user.
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class, 'user_id');
    }

    /**
     * Get the active membership for the user.
     */
    public function activeMembership()
    {
        return $this->hasOne(Membership::class, 'user_id')
            ->where('status', 1)
            ->where('expire_date', '>=', now())
            ->latest();
    }

    /**
     * Check if user is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active === true || $this->active === 1;
    }

    /**
     * Check if user is featured
     *
     * @return bool
     */
    public function isFeatured(): bool
    {
        return $this->featured === 1;
    }

    /**
     * Check if user has active subscription
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeMembership()->exists();
    }

    /**
     * Scope a query to only include tenant users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTenants($query)
    {
        return $query->where('account_type', 'tenant');
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include featured users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', 1);
    }

    /**
     * General API settings associated with the tenant.
     */
    public function generalSetting()
    {
        return $this->hasOne(GeneralSetting::class, 'user_id', 'id');
    }

    /**
     * Alias for generalSetting to match legacy relationship name.
     */
    public function generalSettings()
    {
        return $this->generalSetting();
    }

    /**
     * Scope a query to filter by search term.
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
            $q->where('username', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('company_name', 'like', "%{$term}%");
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}

