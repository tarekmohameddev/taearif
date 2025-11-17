<?php

namespace App\Domain\Affiliate\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Affiliate Model
 * 
 * Represents affiliate/partner users in the referral program
 */
class Affiliate extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AffiliateFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_affiliate_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'fullname',
        'bank_name',
        'bank_account_number',
        'iban',
        'commission_percentage',
        'pending_amount',
        'request_status',
        'start_date_value',
        'to_date_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'start_date_value' => 'date',
        'to_date_value' => 'date',
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
     * Get the user associated with this affiliate.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the transactions for this affiliate.
     */
    public function transactions()
    {
        return $this->hasMany(AffiliateTransaction::class, 'affiliate_id');
    }

    /**
     * Scope a query to filter by request status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('request_status', $status);
    }

    /**
     * Scope a query to search affiliates.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('fullname', 'like', "%{$search}%")
              ->orWhere('bank_name', 'like', "%{$search}%")
              ->orWhere('iban', 'like', "%{$search}%")
              ->orWhereHas('user', function ($userQuery) use ($search) {
                  $userQuery->where('username', 'like', "%{$search}%")
                           ->orWhere('email', 'like', "%{$search}%");
              });
        });
    }

    /**
     * Get total earnings.
     *
     * @return float
     */
    public function getTotalEarningsAttribute(): float
    {
        return $this->transactions()->sum('amount');
    }

    /**
     * Get paid earnings.
     *
     * @return float
     */
    public function getPaidEarningsAttribute(): float
    {
        return $this->transactions()->where('type', 'collected')->sum('amount');
    }
}

