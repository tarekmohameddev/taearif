<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserApiCustomerProcedure extends Model
{
    use HasFactory;
    protected $table = 'users_api_customers_procedures';
    protected $fillable = [
        'procedure_name',
        'user_id',
        'color',
        'icon',
        'order',
        'description',
        'is_active',
    ];

    /**
     * Relation with User
    */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customers()
    {
        return $this->hasMany(\App\Models\ApiCustomer::class, 'procedure_id');
    }

    public function getActiveCustomersCountAttribute()
    {
        return $this->customers()->where('is_active', true)->count();
    }

}
