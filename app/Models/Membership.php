<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    public $table = "memberships";

    protected $fillable = [
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
        'package_id',
        'user_id',
        'start_date',
        'expire_date',
        'conversation_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function getDaysOnPackage(): int
    {
        if (empty($this->start_date)) {
            return 0;
        }

        $start = Carbon::parse($this->start_date)->startOfDay();
        $today = now()->startOfDay();

        if ($start->isFuture()) {
            return 0;
        }

        return max(0, (int) $start->diffInDays($today));
    }
}
