<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertySpecification extends Model
{
    use HasFactory;
    public $table = "user_property_specifications";
    protected $fillable = [
        'user_id',
        'property_id',
        'language_id',
        'key',
        'label',
        'value',
    ];

    public static function storeSpecification($userID, $propertyId, $requestData)
    {

        return self::create([
            'user_id' => $userID,
            'property_id' => $propertyId,
            'language_id' => $requestData['language_id'],
            'key' => $requestData['key'],
            'label' => $requestData['label'],
            'value' => $requestData['value'],
        ]);
    }
}
