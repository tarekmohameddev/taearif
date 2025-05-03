<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\PropertyContent;

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

    public function propertyContent()
    {
        return $this->hasMany(PropertyContent::class, 'city_id', 'id');
    }

}
