<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiAffiliateUser extends Model
{
    use HasFactory;
    protected $table = 'api_affiliate_users';

    protected $fillable = [
        'name',
        'user_id',
        'fullname',
        'bank_name',
        'bank_account_number',
        'iban',
        'request_status',
        'commission_percentage',
        'total_commission',
        'withdrawn_amount',
        'pending_amount',
        'total_earned',
        'total_withdrawn',
        'total_pending',
        'total_refunded',
        'total_commission_paid',
        'total_commission_pending',
    ];

    // hide the user_id from the API response
    protected $hidden = [
        'user_id',
        "updated_at",
        "created_at",
        "id",
    ];

    /**
     * Get the user that owns the affiliate user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
