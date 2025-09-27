<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantSetting extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'settings', 'version', 'published_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'published_at' => 'datetime',
    ];
}


