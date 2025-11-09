<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\User\Models\User;
use App\Domain\Billing\Models\Plan;

/**
 * Invoice Model
 *
 * Represents billing invoices/payment records (memberships table)
 * Provides payment management and approval workflows
 */
class Invoice extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\InvoiceFactory::new();
    }

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
     * The attributes that should be hidden.
     *
     * @var array<int, string>
     */

    /**
     * Get the user that owns the invoice.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the plan/package for the invoice.
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
     * Check if invoice is paid/approved
     *
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if invoice is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if invoice is rejected
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === 2;
    }

    /**
     * Check if invoice is trial
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return $this->is_trial === true && $this->trial_days > 0;
    }

    /**
     * Get status text
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            0 => 'pending',
            1 => 'paid',
            2 => 'rejected',
            default => 'unknown',
        };
    }

    /**
     * Get formatted amount
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        if ($this->price == 0) {
            return 'Free';
        }

        return $this->currency_symbol . number_format($this->price, 2);
    }

    /**
     * Scope a query to only include paid invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope a query to only include pending invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope a query to only include rejected invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 2);
    }

    /**
     * Scope a query to only include trial invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTrial($query)
    {
        return $query->where('is_trial', true);
    }

    /**
     * Scope a query to filter by payment method.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Scope a query to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope a query to include recent invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

