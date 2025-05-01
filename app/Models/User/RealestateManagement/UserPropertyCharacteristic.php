<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\UserFacade;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPropertyCharacteristic extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'facade_id',
        'length',
        'width',
        'street_width_north',
        'street_width_south',
        'street_width_east',
        'street_width_west',
        'building_age',
    ];

    public function UserFacade()
    {
        return $this->belongsTo(UserFacade::class);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }




}
