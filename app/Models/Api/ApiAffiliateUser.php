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
    ];
    // hide the user_id from the API response
    protected $hidden = [
        'user_id',
        "updated_at",
        "created_at",
        "id",
        "request_status",
    ];

    /**
     * Get the user that owns the affiliate user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
