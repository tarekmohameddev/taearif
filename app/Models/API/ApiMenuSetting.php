<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiMenuSetting extends Model
{
    use HasFactory;

    protected $table = 'api_menu_settings';

    protected $fillable = [
        'user_id',
        'menu_position',
        'menu_style',
        'mobile_menu_type',
        'is_sticky',
        'is_transparent',
        'status', // 'true' or 'false'
    ];

    protected $casts = [
        'is_sticky' => 'boolean',
        'is_transparent' => 'boolean',
        'menu_position' => 'string',
        'menu_style' => 'string',
        'mobile_menu_type' => 'string',
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
