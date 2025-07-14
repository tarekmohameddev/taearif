<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AffiliateTransaction extends Model
{
    use HasFactory;
    protected $table = 'affiliate_transactions';
    protected $fillable = ['affiliate_id', 'referral_user_id', 'type', 'amount', 'note', 'image'];
    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function affiliate()
    {
        return $this->belongsTo(\App\Models\Api\ApiAffiliateUser::class, 'affiliate_id');
    }
    public function referralUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'referral_user_id');
    }

}
