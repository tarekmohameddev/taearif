<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;
    public $table = "user_amenities";
    protected $fillable = [
        'user_id',
        'language_id',
        'name',
        'slug',
        'icon',
        'status',
        'serial_number'

    ];
    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }


    public static function storeAmenity($userId, $request)
    {
        return self::create([
            'user_id' => $userId,
            'language_id' => $request['language'],
            'status' => $request['status'],
            'name' => $request['name'],
            'slug' => $request['name'],
            'icon' => $request['icon'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public  function updateAmenity($request)
    {
        return $this->update([
            // 'user_id' => $userId,
            // 'language_id' => $request['language'],
            'status' => $request['status'],
            'name' => $request['name'],
            'slug' => $request['name'],
            'icon' => $request['icon'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function propertyAmenities()
    {
        return $this->hasMany(PropertyAmenity::class, 'amenity_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
