<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserApiCustomerPriority extends Model
{
    use HasFactory;

    protected $table = 'users_api_customers_priorities';

    protected $fillable = [
        'user_id','name','value','color','icon','order','is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
