<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantStaticPage extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'page_id', 'components', 'published_data',
    ];

    protected $casts = [
        'components' => 'array',
        'published_data' => 'array',
    ];
}

