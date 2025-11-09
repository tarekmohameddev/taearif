<?php

namespace App\Domain\Referral\Models;

use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Affiliate Transaction Model
 * 
 * Represents commission transactions in the affiliate program
 */
class AffiliateTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\AffiliateTransactionFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'affiliate_transactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'affiliate_id',
        'referral_user_id',
        'type',
        'image',
        'amount',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
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
     * Get the affiliate this transaction belongs to.
     */
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    /**
     * Get the referred user (if applicable).
     */
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referral_user_id');
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('type', 'pending');
    }

    /**
     * Scope a query to only include approved transactions.
     */
    public function scopeApproved($query)
    {
        return $query->where('type', 'approved');
    }

    /**
     * Scope a query to only include paid transactions.
     */
    public function scopePaid($query)
    {
        return $query->where('type', 'paid');
    }

    /**
     * Scope a query to filter by affiliate.
     */
    public function scopeForAffiliate($query, $affiliateId)
    {
        return $query->where('affiliate_id', $affiliateId);
    }
}

