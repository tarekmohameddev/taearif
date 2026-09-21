<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class TenantBrandSourceAsset extends Model
{
    protected $guarded = [];

    protected $casts = [
        'variant_size' => 'integer',
        'last_verified_at' => 'datetime',
        'last_referenced_at' => 'datetime',
    ];
}
