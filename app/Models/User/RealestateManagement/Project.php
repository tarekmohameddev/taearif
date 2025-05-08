<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\ProjectContent;

class Project extends Model
{
    use HasFactory;
    public $table = "user_projects";
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'featured_image',
        'min_price',
        'max_price',
        'latitude',
        'longitude',
        'featured',
        'complete_status',
        'units',
        'completion_date',
        'developer',
        'published',
        'amenities',
    ];

    protected $casts = [
        'amenities' => 'array'
    ];

    public static function storeProject($userId, $request)
    {

        return self::create([
            'user_id' => $userId,
            'featured_image' => $request['featured_image'],
            'min_price' => $request['min_price'],
            'max_price' => $request['max_price'],
            'featured' => $request['featured'],
            'published' => $request['published'],
            'developer' => $request['developer'] ?? 'Unknown Developer',
            'units' => $request['units'] ?? 0,
            'completion_date' => $request['completion_date'] ?? now()->addYear()->toDateString(),
            'complete_status' => $request['complete_status'] ?? 'In Progress',
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'amenities' => $request['amenities'] ?? [],
        ]);
    }

    public   function updateProject($request)
    {

        return $this->update([
            'featured_image' => $request['featured_image'],
            'min_price' => $request['min_price'],
            'max_price' => $request['max_price'],
            'featured' => $request['featured'],
            'complete_status' => $request['complete_status'] ?? 'In Progress',
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'amenities' => $request['amenities'] ?? [],
        ]);
    }

    public  function galleryImages()
    {
        return $this->hasMany(ProjectGalleryImg::class, 'project_id');
    }

    public  function floorplanImages()
    {
        return $this->hasMany(ProjectFloorplanImg::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // public function projectTypes()
    // {
    //     return $this->hasMany(ProjectType::class, 'project_id');
    // }

    public function specifications()
    {
        return $this->hasMany(ProjectSpecification::class, 'project_id');
    }
    public function content()
    {
        return $this->hasOne(ProjectContent::class, 'project_id', 'id')
                    ->where('language_id', session('user_lang_id')); // Or use a helper if available
    }

    public function contents()
    {
        return $this->hasMany(ProjectContent::class, 'project_id', 'id');
    }

    public function projectContents()
    {
        return $this->hasMany(ProjectContent::class, 'project_id');
    }

    public function types()
    {
        return $this->hasMany(ProjectType::class, 'project_id');
    }

    public function amenities()
    {
        return $this->hasMany(PropertyAmenity::class, 'property_id')->with('amenity');
    }

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'user_property_categories', // Pivot table
            'project_id',               // FK to projects
            'category_id'               // FK to categories
        );
    }

    public function getFeaturedImageAttribute($value)
    {
        return asset('storage/' . $value);
    }



}
