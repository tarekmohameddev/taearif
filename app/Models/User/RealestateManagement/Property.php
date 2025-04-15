<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use App\Models\User\RealestateManagement\PropertyAmenity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\ApiUserCategory;


class Property extends Model
{
    use HasFactory;
    public $table = "user_properties";

    protected $casts = [
        'floor_planning_image' => 'array',
        'features' => 'array',
    ];


    protected $fillable = [
        'category_id',
        'region_id',
        'user_id',
        'featured_image',
        'floor_planning_image',
        'video_image',
        'price',
        'purpose',
        'type',
        'beds',
        'bath',
        'area',
        'video_url',
        'status',
        'featured',
        'features',
        'latitude',
        'longitude',

    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(ApiUserCategory::class, 'category_id');
    }


    public static function storeProperty($userId, $request, $featuredImgName, $floorPlanningImage, $videoImage,$featured)
    {
        return self::create([
            'region_id' => $request['region_id'] ?? null,
            'user_id' => $userId,
            'featured_image' => $featuredImgName,
            'floor_planning_image' => $floorPlanningImage ?? null,
            'video_image' => $videoImage,
            'price' => $request['price'],
            'purpose' => $request['transaction_type'] ?? null,
            'type' => $request['type'] ?? null,
            'beds' => $request['beds'] ?? null,
            'bath' => $request['bath'] ?? null,
            'area' => $request['area'],
            'featured' => $featured,
            'features' => $request['features'],
            'video_url' => $request['video_url'] ?? null,
            'status' => $request['status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude']
        ]);
    }

    public function updateProperty($requestData)
    {
        return $this->update([
            'region_id' => $requestData['region_id'] ?? null,
            'featured_image' => $request['featured_image'] ?? $this->featured_image, //73
            'floor_planning_image' => $request['floor_planning_image']?? null,
            'video_image' => $requestData['video_image'] ?? null,
            'price' => $requestData['price'] ?? null,
            'purpose' => $requestData['transaction_type'] ?? null,
            'type' => $requestData['type'] ?? null,
            'beds' => $requestData['beds'] ?? null,
            'bath' => $requestData['bath'] ?? null,
            'area' => $requestData['area'],
            'featured' => $requestData['featured'],
            'video_url' => $request['video_url'] ?? null,
            'status' => $requestData['status'],
            'features' => $requestData['features'],
            'latitude' => $requestData['latitude'],
            'longitude' => $requestData['longitude']
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


}
