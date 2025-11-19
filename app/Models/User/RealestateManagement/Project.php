<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Category;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\ProjectContent;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Project extends Model
{
    use HasFactory;
    public $table = "user_projects";
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'featured_image',
        'video_url',
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

    /**
     * Get the amenities attribute, ensuring it always returns an array.
     * Handles cases where:
     * - Database value is NULL -> returns []
     * - Database value is a JSON array -> returns array
     * - Database value is a JSON string (comma-separated) -> splits and returns array
     * - Database value is already an array -> returns as is
     */
    protected function amenities(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // If already an array, return as is
                if (is_array($value)) {
                    return $value;
                }
                
                // If null, return empty array
                if (is_null($value)) {
                    return [];
                }
                
                // If it's a string, try to decode JSON first
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    
                    // If decoding succeeded
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // If result is an array, return it
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                        // If result is a string (comma-separated), split it
                        if (is_string($decoded)) {
                            return array_filter(array_map('trim', explode(',', $decoded)));
                        }
                    }
                    
                    // If JSON decode failed, try splitting the string directly
                    // This handles cases where it's stored as plain comma-separated string
                    return array_filter(array_map('trim', explode(',', $value)));
                }
                
                // Fallback to empty array
                return [];
            },
        );
    }

    public static function storeProject($userId, $request)
    {

        return self::create([
            'user_id' => $userId,
            'featured_image' => $request['featured_image'],
            'video_url' => $request['video_url'] ?? null,
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
            'video_url' => $request['video_url'] ?? null,
            'min_price' => $request['min_price'],
            'max_price' => $request['max_price'],
            'featured' => $request['featured'],
            'published' => $request['published'] ?? $this->published,
            'complete_status' => $request['complete_status'] ?? 'In Progress',
            'developer' => $request['developer'] ?? $this->developer,
            'units' => $request['units'] ?? $this->units,
            'completion_date' => $request['completion_date'] ?? $this->completion_date,
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'amenities' => $request['amenities'] ?? $this->getAttribute('amenities') ?? [],
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

    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            'user_property_categories', // Pivot table
            'project_id',               // FK to projects
            'category_id'               // FK to categories
        );
    }

    // public function getFeaturedImageAttribute($value)
    // {
    //     return asset('storage/' . $value);
    // }

    public function scopePublished($query)
    {
        return $query->where('published', 1);
    }
    public function properties()
    {
        return $this->hasMany(Property::class, 'project_id');
    }

}
