<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    public $table = "user_projects";
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
    ];

    public static function storeProject($userId, $request)
    {

        return self::create([
            'user_id' => $userId,
            'featured_image' => $request['featured_image'],
            'min_price' => $request['min_price'],
            'max_price' => $request['max_price'],
            'featured' => $request['featured'],
            'developer' => $request['developer'],
            'units' => $request['units'],
            'completion_date' => $request['completion_date'],
            'complete_status' => $request['complete_status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
        ]);
    }

    public   function updateProject($request)
    {

        return $this->update([
            'featured_image' => $request['featured_image'],
            'min_price' => $request['min_price'],
            'max_price' => $request['max_price'],
            'featured' => $request['featured'],
            'complete_status' => $request['complete_status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
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

    public function projectTypes()
    {
        return $this->hasMany(ProjectType::class, 'project_id');
    }

    public function specifications()
    {
        return $this->hasMany(ProjectSpecification::class, 'project_id');
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

}
