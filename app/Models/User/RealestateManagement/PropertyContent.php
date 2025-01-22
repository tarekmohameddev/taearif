<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mews\Purifier\Facades\Purifier;

class PropertyContent extends Model
{
    use HasFactory;

    public $table = "user_property_contents";
    protected $fillable = [
        'user_id',
        'property_id',
        'language_id',
        'category_id',
        'country_id',
        'state_id',
        'city_id',
        'title',
        'slug',
        'address',
        'description',
        // 'price',
        'meta_keyword',
        'meta_description',
    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }

    public function description(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Purifier::clean($value, 'youtube'),
        );
    }

    public static function storePropertyContent($userId, $propertyId, $requestData)
    {
        return self::create([
            'user_id' => $userId,
            'property_id' => $propertyId,
            'language_id' => $requestData['language_id'],
            'category_id' => $requestData['category_id'],
            'country_id' => $requestData['country_id'] ?? null,
            'state_id' => $requestData['state_id'] ?? null,
            'city_id' => $requestData['city_id'],

            'title' => $requestData['title'],
            'slug' => $requestData['slug'],
            'address' => $requestData['address'],
            'description' => $requestData['description'],
            'meta_keyword' => $requestData['meta_keyword'],
            'meta_description' => $requestData['meta_description'],
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

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function propertySpacifications()
    {
        return $this->hasMany(PropertySpecification::class, 'property_id', 'property_id');
    }

    public function galleryImages()
    {
        return $this->hasMany(PropertySliderImg::class, 'property_id', 'property_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
