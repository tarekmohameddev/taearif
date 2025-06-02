<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;


class Property extends Model
{
    use HasFactory;
    public $table = "user_properties";

    protected $casts = [
        'floor_planning_image' => 'array',
        'features' => 'array',
        'faqs' => 'array',
    ];


    protected $fillable = [
        'category_id',
        'region_id',
        'user_id',
        'payment_method',
        'featured_image',
        'floor_planning_image',
        'video_image',
        'price',
        'meter_price',
        'purpose',
        'type',
        'beds',
        'bath',
        'area',
        'video_url',
        'status',
        'featured',
        'features',
        'faqs',
        'latitude',
        'longitude',
        'project_id',
        'region_id',

    ];

    public function displayFaqs(): array
    {
        return collect($this->faqs ?? [])
               ->where('displayOnPage', true)
               ->values()
               ->all();
    }
    public function getFaqsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ApiUserCategory::class, 'category_id', 'id');
    }


    public static function storeProperty($userId, $request, $featuredImgName, $floorPlanningImage, $videoImage,$featured)
    {
        // Ensure default "other" category exists
        $defaultCategory = ApiUserCategory::firstOrCreate(
            ['slug' => 'other'],
            ['name' => 'Other', 'type' => 'property', 'is_active' => 1]
        );

        return self::create([
            'region_id' => $request['region_id'] ?? null,
            'project_id' => $request['project_id'] ?? null,
            'user_id' => $userId,
            'featured_image' => $featuredImgName,
            'floor_planning_image' => $floorPlanningImage ?? null,
            'video_image' => $videoImage,
            'price' => $request['price'],
            'meter_price' => $request['meter_price'] ?? null,
            'purpose' => $request['purpose'] ?? null,
            'type' => $request['type'] ?? null,
            'beds' => $request['beds'] ?? null,
            'bath' => $request['bath'] ?? null,
            'area' => $request['area'],
            'featured' => $featured,
            'features' => $request['features'],
            'video_url' => $request['video_url'] ?? null,
            'status' => $request['status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'category_id' => $request['category_id'] ?? $defaultCategory->id,
            'payment_method' => $request['payment_method'] ?? null,
            'faqs' => $request['faqs'] ?? [],
        ]);
    }

    public function updateProperty($requestData)
    {
        return $this->update([
            'project_id' => $requestData['project_id'] ?? null,
            'region_id' => $requestData['region_id'] ?? null,
            'featured_image' => $requestData['featured_image'] ?? $this->featured_image,
            'floor_planning_image' => $requestData['floor_planning_image'] ?? null,
            'video_image' => $requestData['video_image'] ?? null,
            'price' => $requestData['price'] ?? null,
            'meter_price' => $requestData['meter_price'] ?? $this->meter_price,
            'purpose' => $requestData['purpose'] ?? null,
            'type' => $requestData['type'] ?? null,
            'beds' => $requestData['beds'] ?? null,
            'bath' => $requestData['bath'] ?? null,
            'area' => $requestData['area'] ?? null,
            'featured' => $requestData['featured'] ?? 0,
            'video_url' => $requestData['video_url'] ?? null,
            'status' => $requestData['status'] ?? 0,
            'features' => $requestData['features'] ?? [],
            'latitude' => $requestData['latitude'] ?? null,
            'longitude' => $requestData['longitude'] ?? null,
            'category_id' => $requestData['category_id'] ?? $this->category_id,
            'payment_method' => $requestData['payment_method'] ?? $this->payment_method  ?? null,
            'faqs' => $requestData['faqs'] ?? $this->faqs,

        ]);
    }

    public function contents()
    {
        return $this->hasMany(PropertyContent::class, 'property_id');
    }


    public function content($langId)
    {
        return  $this->contents()->where('language_id', $langId)->first();
    }

    public function galleryImages()
    {
        return $this->hasMany(PropertySliderImg::class, 'property_id');
    }

    public function proertyAmenities()
    {
        return $this->hasMany(PropertyAmenity::class, 'property_id');
    }

    public function specifications()
    {
        return $this->hasMany(PropertySpecification::class, 'property_id', 'id');
    }

    public function wishlists()
    {
        return $this->hasMany(PropertyWishlist::class, 'property_id', 'id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'property_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'property_id');
    }


    public function getFirstContentAttribute()
    {
        return $this->contents->first();
    }

    public function sliderImages()
    {
        return $this->hasMany(PropertySliderImg::class, 'property_id');
    }

    public function amenities()
    {
        return $this->hasMany(PropertyAmenity::class, 'property_id');
    }


    public function UserPropertyCharacteristics()
    {
        return $this->hasOne(UserPropertyCharacteristic::class, 'property_id', 'id');
    }


}
