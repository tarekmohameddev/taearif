<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TenantFormSubmission extends Model
{
    use HasUuids;

    protected $fillable = [
        'id', 'user_id', 'form_type', 'data', 'submitted_at', 'ip', 'user_agent',
    ];

    protected $casts = [
        'data' => 'array',
        'submitted_at' => 'datetime',
    ];
}


