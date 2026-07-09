<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use App\Models\User\UserDistrict;
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
        'created_by',
        'featured_image',
        'video_url',
        'brochure',
        'min_price',
        'max_price',
        'latitude',
        'longitude',
        'city_id',
        'state_id',
        'featured',
        'complete_status',
        'units',
        'completion_date',
        'developer',
        'published',
        'amenities',
    ];

    protected $casts = [
        'featured' => 'boolean',
        'published' => 'boolean',
        'units' => 'integer',
        'complete_status' => 'integer',
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
            set: function ($value) {
                // Normalize the input value and return JSON string for database storage

                // Handle null or empty values
                if (is_null($value) || $value === '') {
                    return json_encode([]);
                }

                // If it's already an array, filter and clean it
                if (is_array($value)) {
                    // Filter out null/empty values and reindex
                    $cleaned = array_values(array_filter($value, function ($item) {
                        return $item !== null && $item !== '';
                    }));
                    return json_encode($cleaned);
                }

                // If it's a string, try to decode as JSON first
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $cleaned = array_values(array_filter($decoded, function ($item) {
                            return $item !== null && $item !== '';
                        }));
                        return json_encode($cleaned);
                    }
                    // If it's a comma-separated string, split it
                    if (strpos($value, ',') !== false) {
                        $cleaned = array_values(array_filter(array_map('trim', explode(',', $value))));
                        return json_encode($cleaned);
                    }
                    // If it's a plain string, wrap it in an array
                    return json_encode([trim($value)]);
                }

                // Fallback to empty array as JSON
                return json_encode([]);
            },
        );
    }

    public static function storeProject($userId, $request, $createdBy = null)
    {

        return self::create([
            'user_id' => $userId,
            'created_by' => $createdBy ?? auth()->id(),
            'featured_image' => $request['featured_image'],
            'video_url' => !empty($request['video_url']) ? $request['video_url'] : null,
            'brochure' => !empty($request['brochure']) ? $request['brochure'] : null,
            'min_price' => $request['min_price'] ?? null,
            'max_price' => $request['max_price'] ?? null,
            'featured' => $request['featured'] ?? 0,
            'published' => $request['published'] ?? 1,
            'developer' => $request['developer'] ?? 'Unknown Developer',
            'units' => $request['units'] ?? 0,
            'completion_date' => $request['completion_date'] ?? now()->addYear()->toDateString(),
            'complete_status' => $request['complete_status'] ?? 0,
            'latitude' => $request['latitude'] ?? null,
            'longitude' => $request['longitude'] ?? null,
            'city_id' => $request['city_id'] ?? null,
            'state_id' => $request['state_id'] ?? null,
            'amenities' => $request['amenities'] ?? [],
        ]);
    }

    public   function updateProject($request)
    {
        $locationKeys = ['city_id', 'state_id', 'district_id'];
        $hasLocationUpdate = ! empty(array_intersect($locationKeys, array_keys($request)));

        return $this->update([
            'featured_image' => $request['featured_image'],
            'video_url' => !empty($request['video_url']) ? $request['video_url'] : null,
            'brochure' => array_key_exists('brochure', $request)
                ? (!empty($request['brochure']) ? $request['brochure'] : null)
                : $this->brochure,
            'min_price' => $request['min_price'] ?? $this->min_price,
            'max_price' => $request['max_price'] ?? $this->max_price,
            'featured' => $request['featured'] ?? $this->featured,
            'published' => $request['published'] ?? $this->published,
            'complete_status' => $request['complete_status'] ?? $this->complete_status ?? 0,
            'developer' => $request['developer'] ?? $this->developer,
            'units' => $request['units'] ?? $this->units,
            'completion_date' => $request['completion_date'] ?? $this->completion_date,
            'latitude' => $request['latitude'] ?? $this->latitude,
            'longitude' => $request['longitude'] ?? $this->longitude,
            'city_id' => $hasLocationUpdate ? ($request['city_id'] ?? null) : $this->city_id,
            'state_id' => $hasLocationUpdate ? ($request['state_id'] ?? null) : $this->state_id,
            'amenities' => $request['amenities'] ?? $this->amenities ?? [],
        ]);
    }

    public function district()
    {
        return $this->belongsTo(UserDistrict::class, 'state_id', 'id');
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
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

    public function buildings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Building::class, 'project_id');
    }

}
