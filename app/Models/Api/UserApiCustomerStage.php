<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserApiCustomerStage extends Model
{
    use HasFactory;
    protected $table = 'users_api_customers_stages';
    protected $fillable = [
        'stage_name',
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
}
