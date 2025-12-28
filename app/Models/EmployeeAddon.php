<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAddon extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'employee_addons';

    protected $fillable = [
        'user_id',
        'plan_id',
        'qty',
        'amount',
        'status',
        'payment_ref',
        'gateway_transaction_id',
        'expire_date',
    ];

    protected $casts = [
        'qty' => 'integer',
        'amount' => 'decimal:2',
        'status' => 'string',
        'expire_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(EmployeeAddonPlan::class, 'plan_id');
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * Scope to get approved and not expired addons for a user.
     */
    public function scopeActiveFor($query, int $userId)
    {
        return $query->where('user_id', $userId)
            ->where('status', self::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('expire_date')
                  ->orWhere('expire_date', '>=', now());
            });
    }
}
