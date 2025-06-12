<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Isthara extends Model
{
    use HasFactory;
    protected $table = 'isthara';
    protected $fillable = [
        'name',
        'phone',
        'is_read',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'phone' => 'string',
        'is_read' => 'boolean',
    ];
}
