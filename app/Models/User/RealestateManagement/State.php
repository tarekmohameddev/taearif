<?php

namespace App\Models\User\RealestateManagement;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    public $table = "user_states";
    protected $fillable = [
        'user_id',
        'language_id',
        'country_id',
        'name',
        'slug',
        'serial_number'
    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }


    public static function storeState($userId, $request, $coutryId = null)
    {
        return self::create([
            'user_id' => $userId,
            'language_id' => $request['language'],
            'country_id' => $coutryId,
            'name' => $request['name'],
            'slug' => $request['name'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public  function updateState($request)
    {
        return $this->update([
            'name' => $request['name'],
            'slug' => $request['name'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }
    public function propertyContents()
    {
        return $this->hasMany(PropertyContent::class, 'state_id', 'id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'state_id', 'id');
    }
}
