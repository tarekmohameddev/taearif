<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySliderImg extends Model
{
    use HasFactory;

    public $table = "user_property_slider_imgs";
    protected $fillable = [
        'user_id',
        'property_id',
        'image',
    ];

    public static function storeSliderImage($userId, $propertyId, $imageName)
    {
        return self::create([
            'user_id' => $userId,
            'property_id' => $propertyId,
            'image' => $imageName
        ]);
    }
}
