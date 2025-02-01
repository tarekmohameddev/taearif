<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAmenity extends Model
{
    use HasFactory;
    public $table = "user_property_amenities";
    protected $fillable = [
        'user_id',
        'property_id',
        'amenity_id'
    ];

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public static function sotreAmenity($userId, $propertyId, $amenityId)
    {
        return self::create([
            'user_id' => $userId,
            'property_id' => $propertyId,
            'amenity_id' => $amenityId,
        ]);
    }
    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id');
    }
}
