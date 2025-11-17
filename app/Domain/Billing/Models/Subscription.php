<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\User\Models\User;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Invoice;

/**
 * Subscription Model
 *
 * Represents user subscriptions (memberships table)
 * Alias for Membership model
 */
class Subscription extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'memberships';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'package_id',
        'package_price',
        'discount',
        'coupon_code',
        'price',
        'currency',
        'currency_symbol',
        'payment_method',
        'transaction_id',
        'status',
        'is_trial',
        'trial_days',
        'receipt',
        'transaction_details',
        'settings',
        'start_date',
        'expire_date',
        'modified',
        'conversation_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'package_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'price' => 'decimal:2',
        'status' => 'integer',
        'is_trial' => 'boolean',
        'trial_days' => 'integer',
        'modified' => 'boolean',
        'start_date' => 'date',
        'expire_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan for the subscription.
     */
    public function package()
    {
        return $this->belongsTo(Plan::class, 'package_id');
    }

    /**
     * Alternative accessor for plan
     */
    public function plan()
    {
        return $this->package();
    }

    /**
     * Get the most recent invoice associated with the subscription.
     */
    public function latestInvoice()
    {
        return $this->hasOne(Invoice::class, 'id', 'id');
    }

    /**
     * Check if subscription is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $today = now()->toDateString();

        return $this->status === 1
            && $this->expire_date
            && $this->expire_date >= $today
            && (!$this->start_date || $this->start_date <= $today);
    }

    /**
     * Check if subscription is trial
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->is_trial === true && $this->trial_days > 0;
    }

    /**
     * Check if subscription is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expire_date && $this->expire_date < now()->toDateString();
    }

    /**
     * Get days until expiration
     *
     * @return int|null
     */
    public function getDaysUntilExpirationAttribute(): ?int
    {
        if (!$this->expire_date) {
            return null;
        }

        return now()->diffInDays($this->expire_date, false);
    }

    /**
     * Scope a query to only include active subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();

        return $query->where('status', 1)
                    ->where('expire_date', '>=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('start_date')
                          ->orWhere('start_date', '<=', $today);
                    });
    }

    /**
     * Scope a query to only include expired subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expire_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only include trial subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    /**
     * Scope a query to include expiring soon subscriptions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 1)
                    ->whereBetween('expire_date', [
                        now()->toDateString(),
                        now()->addDays($days)->toDateString()
                    ]);
    }
}

