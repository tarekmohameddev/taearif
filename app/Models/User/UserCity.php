<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCity extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name_ar',
        'name_en',
        'country_id',
        'region_id',
        'latitude',
        'longitude',
    ];
}
