<?php

namespace App\Models\Api\marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class UserCredit extends Model
{
    use HasFactory;

    protected $table = 'user_credits';

    protected $fillable = [
        'user_id',
        'total_credits',
        'used_credits',
        'reserved_credits',
        'monthly_limit',
        'average_cost_per_credit',
        'reset_date',
        'is_active',
    ];

    protected $casts = [
        'total_credits' => 'integer',
        'used_credits' => 'integer',
        'reserved_credits' => 'integer',
        'monthly_limit' => 'integer',
        'average_cost_per_credit' => 'decimal:4',
        'reset_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(CreditTransaction::class, 'user_id', 'user_id');
    }

    /**
     * Get available credits (total - used - reserved)
     */
    public function getAvailableCreditsAttribute()
    {
        $reserved = (int) ($this->reserved_credits ?? 0);

        return $this->total_credits - $this->used_credits - $reserved;
    }

    /**
     * Get monthly usage percentage
     */
    public function getMonthlyUsagePercentageAttribute()
    {
        if ($this->monthly_limit <= 0) {
            return 0;
        }
        return round(($this->used_credits / $this->monthly_limit) * 100, 1);
    }

    /**
     * Check if user has enough credits
     */
    public function hasEnoughCredits($creditsNeeded)
    {
        return $this->available_credits >= $creditsNeeded;
    }

    /**
     * Check if user is within monthly limit
     * Monthly limits are disabled - always returns true
     */
    public function isWithinMonthlyLimit($additionalCredits = 0)
    {
        // Monthly limits disabled - users can use all their credits without restrictions
        return true;
        
        // Original logic (commented out):
        // return ($this->used_credits + $additionalCredits) <= $this->monthly_limit;
    }

    /**
     * Use credits
     */
    public function useCredits($credits, $description = null)
    {
        if (!$this->hasEnoughCredits($credits)) {
            return false;
        }

        // Monthly limit check removed - users can use all their credits without monthly restrictions
        // if (!$this->isWithinMonthlyLimit($credits)) {
        //     return false;
        // }

        $this->increment('used_credits', $credits);

        // Create transaction record
        CreditTransaction::create([
            'user_id' => $this->user_id,
            'transaction_type' => 'usage',
            'credits_amount' => -$credits, // Negative for usage
            'status' => 'completed',
            'description' => $description ?? 'Credit usage',
            'metadata' => [
                'usage_type' => 'marketing_channel',
                'timestamp' => now()->toISOString(),
            ]
        ]);

        return true;
    }

    /**
     * Add credits
     */
    public function addCredits(
        $credits,
        $packageId = null,
        $description = null,
        ?CreditTransaction $existingTransaction = null
    )
    {
        if ($existingTransaction) {
            if ((int) $existingTransaction->user_id !== (int) $this->user_id) {
                throw new \InvalidArgumentException('The credit transaction belongs to a different user.');
            }

            $existingTransaction->fill([
                'credit_package_id' => $packageId ?? $existingTransaction->credit_package_id,
                'credits_amount' => $credits,
                'status' => 'completed',
                'description' => $description ?? $existingTransaction->description ?? 'Credit purchase',
            ])->save();

            $this->increment('total_credits', $credits);

            return true;
        }

        $this->increment('total_credits', $credits);

        // Create transaction record
        CreditTransaction::create([
            'user_id' => $this->user_id,
            'credit_package_id' => $packageId,
            'transaction_type' => 'purchase',
            'credits_amount' => $credits,
            'status' => 'completed',
            'description' => $description ?? 'Credit purchase',
        ]);

        return true;
    }

    /**
     * Reset monthly usage (call this monthly)
     */
    public function resetMonthlyUsage()
    {
        $this->update([
            'used_credits' => 0,
            'reset_date' => now()->addMonth(),
        ]);
    }

    /**
     * Check if monthly reset is needed
     */
    public function needsMonthlyReset()
    {
        if (!$this->reset_date) {
            return true;
        }

        return now()->isAfter($this->reset_date);
    }

    /**
     * Get or create user credit record
     */
    public static function getOrCreateForUser($userId)
    {
        $userCredit = self::where('user_id', $userId)->first();

        if (!$userCredit) {
            $userCredit = self::create([
                'user_id' => $userId,
                'total_credits' => 0,
                'used_credits' => 0,
                'monthly_limit' => 2147483647, // Maximum safe integer value (effectively unlimited)
                'average_cost_per_credit' => 0.05,
                'reset_date' => now()->addMonth(),
                'is_active' => true,
            ]);
        }

        // Check if monthly reset is needed
        if ($userCredit->needsMonthlyReset()) {
            $userCredit->resetMonthlyUsage();
        }

        return $userCredit;
    }

    /**
     * Get credit costs for different message types
     * @deprecated Use getCostForMessageType() which reads from database
     */
    public static function getMessageTypeCosts()
    {
        return [
            'whatsapp' => 1,
            'sms' => 1,
            'facebook' => 1,
            'telegram' => 1,
            'instagram' => 1,
        ];
    }

    /**
     * Get cost for message type from MarketingChannelPricing table
     * Requires pricing to be configured - throws exception if not found
     * 
     * @param string $messageType The channel type (e.g., 'sms', 'whatsapp')
     * @return int Credits required per message
     * @throws \App\Domain\Communication\Exceptions\ChannelPricingNotConfiguredException If pricing is not configured for the channel type
     */
    public static function getCostForMessageType($messageType)
    {
        $pricing = MarketingChannelPricing::active()
            ->forChannel($messageType)
            ->first();
        
        if (!$pricing) {
            throw new \App\Domain\Communication\Exceptions\ChannelPricingNotConfiguredException($messageType);
        }
        
        return $pricing->credits_per_message;
    }
}
