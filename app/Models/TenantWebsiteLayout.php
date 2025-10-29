<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantWebsiteLayout extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'data', 'published_data',
    ];

    protected $casts = [
        'data' => 'array',
        'published_data' => 'array',
    ];
}



