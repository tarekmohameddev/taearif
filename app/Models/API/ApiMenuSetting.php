<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiMenuSetting extends Model
{
    use HasFactory;

    protected $table = 'api_menu_items';

    protected $fillable = [
        'menu_position',
        'menu_style',
        'mobile_menu_type',
        'is_sticky',
        'is_transparent',
        'user_id',
        'label',
        'url',
        'is_external',
        'is_active',
        'order',
        'parent_id',
        'show_on_mobile',
        'show_on_desktop',

    ];

    protected $casts = [
        'is_sticky' => 'boolean',
        'is_transparent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function children()
    {
        return $this->hasMany(ApiMenuSetting::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(ApiMenuSetting::class, 'parent_id');
    }
}
