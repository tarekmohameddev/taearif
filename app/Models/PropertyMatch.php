<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyMatch extends Model
{
    use HasFactory;

    protected $table = 'property_matches';

    protected $fillable = [
        'user_id',
        'customer_key',
        'request_type',
        'request_id',
        'property_id',
        'match_score',
        'database_score',
        'ai_score',
        'match_explanation',
        'matched_criteria',
        'is_reviewed',
        'is_contacted',
    ];

    protected $casts = [
        'matched_criteria' => 'array',
        'is_reviewed' => 'boolean',
        'is_contacted' => 'boolean',
    ];
}


