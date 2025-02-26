<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Property extends Model
{
    use HasFactory;

    public $table = "user_properties";
    protected $fillable = [
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
        'latitude',
        'longitude',

    ];



    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }


    public static function storeProperty($userId, $request, $featuredImgName, $floorPlanningImage, $videoImage)
    {
        return self::create([
            'user_id' => $userId,
            'featured_image' => $featuredImgName,
            'floor_planning_image' => $floorPlanningImage ?? null,
            'video_image' => $videoImage,
            'price' => $request['price'],
            'purpose' => $request['purpose'] ?? null,
            'type' => $request['type'] ?? null,
            'beds' => $request['beds'] ?? null,
            'bath' => $request['bath'] ?? null,
            'area' => $request['area'],
            'video_url' => $request['video_url'],
            'status' => $request['status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude']
        ]);
    }

    public function updateProperty($requestData)
    {
        return $this->update([
            'featured_image' => $requestData['featured_image'],
            'floor_planning_image' => $requestData['floor_planning_image']?? null,
            'video_image' => $requestData['video_image'],
            'price' => $requestData['price'],
            'purpose' => $requestData['purpose'] ?? null,
            'type' => $requestData['type'] ?? null,
            'beds' => $requestData['beds'] ?? null,
            'bath' => $requestData['bath'] ?? null,
            'area' => $requestData['area'],
            'video_url' => $requestData['video_url'],
            'status' => $requestData['status'],
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



}
