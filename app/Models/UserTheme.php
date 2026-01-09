<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Api\ApiThemeSettings;
use App\Models\User;

class UserTheme extends Model
{
    use HasFactory;

    protected $table = 'user_themes';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'theme_id',
        'purchased_at',
        'status',
        'payment_ref',
        'gateway_transaction_id',
        'amount_paid',
        'currency',
        'payment_method',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    /**
     * Get the user who owns this theme
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the theme details
     */
    public function theme()
    {
        return $this->belongsTo(ApiThemeSettings::class, 'theme_id', 'theme_id');
    }

    /**
     * Check if purchase is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Scope to get only active purchases
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Get status options
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_REJECTED,
        ];
    }
}
