<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerDropdownSetting extends Model
{
    use HasFactory;

    protected $table = 'api_customer_dropdown_settings';

    protected $fillable = [
        'user_id',
        'is_visible',
        'show_login',
        'show_register',
        'show_dashboard',
        'show_logout',
        'additional_settings',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'show_login' => 'boolean',
        'show_register' => 'boolean',
        'show_dashboard' => 'boolean',
        'show_logout' => 'boolean',
        'additional_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
