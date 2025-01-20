<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyContact extends Model
{
    use HasFactory;
    public $table = "user_property_contacts";
    protected $fillable = [
        'user_id',
        'property_id',
        'name',
        'phone',
        'email',
        'message',
    ];

    public static function createContact($userId,   $request)
    {
        return  self::create([
            'user_id' => $userId,
            'property_id' => $request['property_id'],
            'name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'message' => $request['message'],
        ]);
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function propertyContent()
    {
        return $this->belongsTo(PropertyContent::class, 'property_id', 'property_id');
    }
}
