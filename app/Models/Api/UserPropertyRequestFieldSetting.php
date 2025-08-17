<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class UserPropertyRequestFieldSetting extends Model
{
    protected $table = 'user_property_request_field_settings';

    protected $fillable = [
        'user_id','field_key','is_visible','is_required','sort_order',
        'label_ar','label_en','meta',
    ];

    protected $casts = [
        'is_visible'  => 'boolean',
        'is_required' => 'boolean',
        'meta'        => 'array',
    ];
}
