<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiThemeSettings extends Model
{
    use HasFactory;
    protected $table = 'api_themes_settings';

    protected $fillable = [
        'theme_id',
        'name',
        'description',
        'thumbnail',
        'category',
        'active',
        'popular',
    ];

    protected $casts = [
        'active' => 'boolean',
        'popular' => 'boolean',
    ];

}

