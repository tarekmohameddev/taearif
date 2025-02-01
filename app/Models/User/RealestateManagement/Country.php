<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;
    public $table = "user_countries";
    protected $fillable = [
        'user_id',
        'language_id',
        'name',
        'serial_number'
    ];

    public static function storeCountry($userId, $request)
    {
        return self::create([
            'user_id' => $userId,
            'language_id' => $request['language'],
            'name' => $request['name'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function updateCountry($request)
    {
        return $this->update([
            'name' => $request['name'],
            'serial_number' => $request['serial_number']
        ]);
    }

    public function states()
    {
        return $this->hasMany(State::class, 'country_id', 'id');
    }

    public function cities()
    {
        return $this->hasMany(City::class, 'country_id', 'id');
    }

    public function propertyContents()
    {
        return $this->hasMany(PropertyContent::class, 'country_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
