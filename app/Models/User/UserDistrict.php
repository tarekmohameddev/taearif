<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDistrict extends Model
{
    use HasFactory;
    protected $table = 'user_districts';

    protected $fillable = [
        'id',
        'name_ar',
        'name_en',
        'city_id',
        'city_name_ar',
        'city_name_en',
        'country_name_ar',
        'country_name_en',
    ];
    protected $casts = [
        'id' => 'integer',
        'city_id' => 'integer',
    ];
    public function city()
    {
        return $this->belongsTo(UserCity::class, 'city_id', 'id');
    }
    public function propertyContent()
    {
        return $this->hasMany(\App\Models\User\RealestateManagement\PropertyContent::class, 'state_id', 'id');
    }

}
