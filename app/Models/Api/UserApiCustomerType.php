<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class UserApiCustomerType extends Model
{
    use HasFactory;

    protected $table = 'users_api_customers_types';

    protected $fillable = [
        'user_id',
        'name',
        'value',
        'color',
        'icon',
        'order',
        'is_active',
    ];

    /**
     * Relation with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
