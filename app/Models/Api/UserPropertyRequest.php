<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPropertyRequest extends Model
{
    use HasFactory;

    protected $table = 'users_property_requests';
// wants_similar_offers purchase_method region
    protected $fillable = [
        'user_id',
        'region', // nullable
        'property_type',
        'category_id',
        'city_id',
        'districts_id',
        'area_from',
        'area_to',
        'purchase_method', // nullable
        'budget_from',
        'budget_to',
        'seriousness',
        'purchase_goal',
        'wants_similar_offers', // nullable
        'full_name',
        'phone',
        'contact_on_whatsapp',
        'notes',
        'is_read',
        'is_active',
    ];

    protected $casts = [
        'wants_similar_offers' => 'boolean',
        'contact_on_whatsapp'  => 'boolean',
        'budget_from'          => 'float',
        'budget_to'            => 'float',
        'area_from'            => 'integer',
        'area_to'              => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
