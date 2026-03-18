<?php

namespace App\Models\Api\marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditTransaction extends Model
{
    use HasFactory;

    protected $table = 'credit_transactions';

    protected $fillable = [
        'user_id',
        'credit_package_id',
        'transaction_type',
        'credits_amount',
        'amount_paid',
        'currency',
        'payment_method',
        'payment_transaction_id',
        'status',
        'reference_number',
        'description',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'credits_amount' => 'integer',
        'amount_paid' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function creditPackage()
    {
        return $this->belongsTo(CreditPackage::class, 'credit_package_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get transaction type display name
     */
    public function getTransactionTypeDisplayAttribute()
    {
        $types = [
            'purchase' => 'Credit Purchase',
            'usage' => 'Credit Usage',
            'refund' => 'Credit Refund',
            'admin_add' => 'Admin Credit Addition',
            'admin_remove' => 'Admin Credit Removal',
        ];

        return $types[$this->transaction_type] ?? $this->transaction_type;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayAttribute()
    {
        $statuses = [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Check if transaction is positive (adds credits)
     */
    public function isPositive()
    {
        return $this->credits_amount > 0;
    }

    /**
     * Check if transaction is negative (uses credits)
     */
    public function isNegative()
    {
        return $this->credits_amount < 0;
    }

    /**
     * Get absolute credit amount
     */
    public function getAbsoluteCreditsAttribute()
    {
        return abs($this->credits_amount);
    }

    /**
     * Scope for purchases
     */
    public function scopePurchases($query)
    {
        return $query->where('transaction_type', 'purchase');
    }

    /**
     * Scope for usage
     */
    public function scopeUsage($query)
    {
        return $query->where('transaction_type', 'usage');
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for refunded transactions
     */
    public function scopeRefunded($query)
    {
        return $query->where('status', 'refunded');
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Generate unique reference number
     */
    public static function generateReferenceNumber()
    {
        do {
            $reference = 'CT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Create purchase transaction
     */
    public static function createPurchase($userId, $packageId, $credits, $amount, $paymentMethod = null, $paymentTransactionId = null, $metadata = [])
    {
        return self::create([
            'user_id' => $userId,
            'credit_package_id' => $packageId,
            'transaction_type' => 'purchase',
            'credits_amount' => $credits,
            'amount_paid' => $amount,
            'currency' => 'SAR',
            'payment_method' => $paymentMethod,
            'payment_transaction_id' => $paymentTransactionId,
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber(),
            'description' => 'Credit package purchase',
            'metadata' => array_merge($metadata, [
                'purchase_date' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Create usage transaction
     */
    public static function createUsage($userId, $credits, $description = null, $metadata = [])
    {
        return self::create([
            'user_id' => $userId,
            'transaction_type' => 'usage',
            'credits_amount' => -$credits, // Negative for usage
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber(),
            'description' => $description ?? 'Credit usage',
            'metadata' => array_merge($metadata, [
                'usage_date' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Create admin transaction
     */
    public static function createAdminTransaction($userId, $credits, $type, $description, $adminId, $metadata = [])
    {
        if (!in_array($type, ['admin_add', 'admin_remove'])) {
            throw new \InvalidArgumentException('Invalid admin transaction type');
        }

        return self::create([
            'user_id' => $userId,
            'transaction_type' => $type,
            'credits_amount' => $type === 'admin_add' ? $credits : -$credits,
            'status' => 'completed',
            'reference_number' => self::generateReferenceNumber(),
            'description' => $description,
            'created_by' => $adminId,
            'metadata' => array_merge($metadata, [
                'admin_action_date' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Get transaction statistics for user
     */
    public static function getUserStatistics($userId, $startDate = null, $endDate = null)
    {
        $query = self::where('user_id', $userId);

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        $transactions = $query->get();

        return [
            'total_purchases' => $transactions->where('transaction_type', 'purchase')->where('status', 'completed')->count(),
            'total_usage' => $transactions->where('transaction_type', 'usage')->where('status', 'completed')->count(),
            'total_credits_purchased' => $transactions->where('transaction_type', 'purchase')->where('status', 'completed')->sum('credits_amount'),
            'total_credits_used' => abs($transactions->where('transaction_type', 'usage')->where('status', 'completed')->sum('credits_amount')),
            'total_amount_paid' => $transactions->where('transaction_type', 'purchase')->where('status', 'completed')->sum('amount_paid'),
            'pending_transactions' => $transactions->where('status', 'pending')->count(),
            'failed_transactions' => $transactions->where('status', 'failed')->count(),
        ];
    }
}
