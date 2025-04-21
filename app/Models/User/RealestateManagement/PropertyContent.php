<?php

namespace App\Models\User\RealestateManagement;

use App\Models\Sale;
use App\Models\User;
use App\Models\Contract;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\State;
use App\Models\User\RealestateManagement\Country;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertySpecification;

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
        'price',
        'meta_keyword',
        'meta_description',
    ];

    public function slug(): Attribute
    {
        return Attribute::make(
            set: fn($value) => make_slug($value),
        );
    }

    public static function generateUniqueSlug($title, $propertyId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (self::where('slug', $slug)->when($propertyId, fn($q) => $q->where('property_id', '!=', $propertyId))->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
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
            // 'slug' => $requestData['slug'],
            'slug' => self::generateUniqueSlug($requestData['title'], $propertyId),
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

    // contracts
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'property_id', 'property_id');
    }

    // sales
    public function sales()
    {
        return $this->hasMany(Sale::class, 'property_id', 'property_id');
    }
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
