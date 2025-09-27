<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantMedia extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'disk', 'path', 'url', 'mime', 'size', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}


