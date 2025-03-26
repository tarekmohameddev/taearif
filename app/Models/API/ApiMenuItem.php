<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ApiMenuItem extends Model
{
    use HasFactory;

    protected $table = 'api_menu_items';

    protected $fillable = [
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
        'is_external' => 'boolean',
        'is_active' => 'boolean',
        'show_on_mobile' => 'boolean',
        'show_on_desktop' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ApiMenuItem::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ApiMenuItem::class, 'parent_id')->orderBy('order');
    }
}
