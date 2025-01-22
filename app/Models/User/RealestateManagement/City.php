<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    public $table = "user_cities";

    protected $fillable = [
        'user_id',
        'language_id',
        'country_id',
        'state_id',
        'name',
        'featured',
        'slug',
        'image',
        'status',
        'serial_number'
    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }

    public static function storeCity($userId, $request, $image, $countryId = null, $stateId = null)
    {
        return self::create([
            'user_id' => $userId,
            'language_id' => $request['language'],
            'country_id' => $countryId,
            'state_id' => $stateId,
            'name' => $request['name'],
            'slug' => $request['name'],
            'status' => $request['status'],
            'image' => $image,
            'serial_number' => $request['serial_number']
        ]);
    }

    public function updateCity($request, $image)
    {
        return $this->update([
            'name' => $request['name'],
            'slug' => $request['name'],
            'status' => $request['status'],
            'image' => $image,
            'serial_number' => $request['serial_number']
        ]);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
    public function propertyContent()
    {
        return $this->hasMany(PropertyContent::class, 'city_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
