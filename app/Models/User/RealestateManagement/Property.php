<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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
        'pricePerMeter',
        'purpose',
        'type',
        'beds',
        'bath',
        'area',
        'size',
        'video_url',
        'virtual_tour',
        'status',
        'property_status',
        'featured',
        'features',
        'faqs',
        'latitude',
        'longitude',
        'project_id',
        'reorder',
        'reorder_featured',
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

        $reorderFeatured = 0;
        if ($featured) {
            $last = self::where('featured', 1)->max('reorder_featured');
            $reorderFeatured = $last ? $last + 1 : 1;
        }
        return self::create([
            'region_id' => $request['region_id'] ?? null,
            'project_id' => $request['project_id'] ?? null,
            'user_id' => $userId,
            'featured_image' => $featuredImgName,
            'floor_planning_image' => $floorPlanningImage ?? null,
            'video_image' => $videoImage,
            'price' => $request['price'],
            'pricePerMeter' => $request['pricePerMeter'] ?? null,
            'purpose' => $request['purpose'] ?? null,
            'type' => $request['type'] ?? null,
            'beds' => $request['beds'] ?? null,
            'bath' => $request['bath'] ?? null,
            'area' => $request['area'],
            'size' => $request['size'] ?? null,
            'featured' => $featured,
            'features' => $request['features'],
            'video_url' => $request['video_url'] ?? null,
            'virtual_tour' => $request['virtual_tour'] ?? null,
            'status' => $request['status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'category_id' => $request['category_id'] ?? $defaultCategory->id,
            'payment_method' => $request['payment_method'] ?? null,
            'faqs' => $request['faqs'] ?? [],
            'reorder_featured' => $reorderFeatured,
            'reorder' => 0,
        ]);
    }

    public function updateProperty($requestData)
    {
        if (($requestData['featured'] ?? 0) && !$this->reorder_featured) {
            $last = self::where('featured', 1)->max('reorder_featured');
            $updates['reorder_featured'] = $last ? $last + 1 : 1;
        }

        return $this->update([
            'project_id' => $requestData['project_id'] ?? null,
            'region_id' => $requestData['region_id'] ?? null,
            'featured_image' => $requestData['featured_image'] ?? $this->featured_image,
            'floor_planning_image' => $requestData['floor_planning_image'] ?? null,
            'video_image' => $requestData['video_image'] ?? null,
            'price' => $requestData['price'] ?? null,
            'pricePerMeter' => $requestData['pricePerMeter'] ?? $this->pricePerMeter,
            'purpose' => $requestData['purpose'] ?? null,
            'type' => $requestData['type'] ?? null,
            'beds' => $requestData['beds'] ?? null,
            'bath' => $requestData['bath'] ?? null,
            'area' => $requestData['area'] ?? null,
            'size' => $requestData['size'] ?? $this->size,
            'featured' => $requestData['featured'] ?? 0,
            'video_url' => $requestData['video_url'] ?? null,
            'virtual_tour' => $requestData['virtual_tour'] ?? null,
            'status' => $requestData['status'] ?? 0,
            'features' => $requestData['features'] ?? [],
            'latitude' => $requestData['latitude'] ?? null,
            'longitude' => $requestData['longitude'] ?? null,
            'category_id' => $requestData['category_id'] ?? $this->category_id,
            'payment_method' => $requestData['payment_method'] ?? $this->payment_method  ?? null,
            'faqs' => $requestData['faqs'] ?? $this->faqs,
            'reorder_featured' => $requestData['reorder_featured'] ?? $this->reorder_featured,
            'reorder' => $requestData['reorder'] ?? $this->reorder,
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
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function rentals()
    {
        return $this->hasMany(\App\Models\Api\Rms\RmRental::class, 'unit_id');
    }

    public function activeRentals()
    {
        return $this->hasMany(\App\Models\Api\Rms\RmRental::class, 'unit_id')->where('status', 'active');
    }

    public function updatePropertyStatus()
    {
        $hasActiveRentals = $this->activeRentals()->exists();
        $this->update(['property_status' => $hasActiveRentals ? 'rented' : 'available']);
    }


       /** Canonical public dir for property images */
    private const CANON_DIR = 'properties';

    /** Legacy dirs we want to support for old rows */
    private const LEGACY_DIRS = [
        'properties-img',
        'assets/img/property/featureds',
        'storage/properties',
    ];

    /* -------------------------
       MUTATORS (normalize on save)
       ------------------------- */
    protected function featuredImage(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::normalizeImagePath($value)
        );
    }

    protected function videoImage(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => self::normalizeImagePath($value)
        );
    }

    protected function floorPlanningImage(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (empty($value)) {
                    return ['floor_planning_image' => null];
                }
                $arr = is_array($value) ? $value : [$value];
                $normalized = array_map([self::class, 'normalizeImagePath'], $arr);
                return ['floor_planning_image' => $normalized];
            }
        );
    }
    // Accept both string or array
    public function setFloorPlanningImageAttribute($value)
    {
        $values = is_array($value) ? $value : (empty($value) ? [] : [$value]);
        $normalized = array_values(array_filter(array_map([self::class, 'normalizeImagePath'], $values)));

        // $casts['floor_planning_image' => 'array'], Eloquent will
        // store this as JSON automatically
        $this->attributes['floor_planning_image'] = json_encode($normalized);
    }

    private static function normalizeImagePath($value): ?string
    {
        if (empty($value)) return null;

        // protocol-relative => treat as https
        if (Str::startsWith($value, '//')) {
            $value = 'https:' . $value;
        }

        // Absolute URL?
        if (Str::startsWith($value, ['http://', 'https://'])) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $urlHost = parse_url($value, PHP_URL_HOST);
            $urlPath = parse_url($value, PHP_URL_PATH) ?: '';

            // Different host (CDN etc.) => keep absolute (don’t touch)
            if ($appHost && $urlHost && $appHost !== $urlHost) {
                return $value;
            }

            // Same host => use path
            $value = $urlPath;
        }

        // Now $value is a path. Canonicalize: keep only filename, store under CANON_DIR
        $path = ltrim($value, '/');
        $filename = basename($path);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        return self::CANON_DIR . '/' . $filename;
    }

    /* -------------------------
       ACCESSOR (smart URL for output)
       ------------------------- */
    protected $appends = ['featured_image_url'];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return self::resolvePublicUrl($this->featured_image);
    }

    // If you expose gallery with legacy rows, you might want similar accessors
    // public function getVideoImageUrlAttribute(): ?string { ... }

    private static function resolvePublicUrl(?string $path): ?string
    {
        if (empty($path)) return null;

        // If already absolute URL, just return it
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $candidates = [];

        // 1) Given path as-is (already normalized for new rows)
        $candidates[] = ltrim($path, '/');

        // 2) Canonical folder + filename (handles old rows that stored full/other dirs)
        $filename = basename($path);
        $candidates[] = self::CANON_DIR . '/' . $filename;

        // 3) Legacy dirs + filename (fallbacks for old files)
        foreach (self::LEGACY_DIRS as $dir) {
            $candidates[] = rtrim($dir, '/') . '/' . $filename;
        }

        // Return the first existing file URL, else fallback to asset of original
    // inside resolvePublicUrl()
    foreach ($candidates as $rel) {
        // check public/ first
        if (file_exists(public_path($rel))) {
            return asset($rel);
        }
        // also check storage disk (optional)
        $relWithoutStorage = preg_replace('#^storage/#', '', $rel);
        if (Storage::disk('public')->exists($relWithoutStorage)) {
            return asset('storage/'.$relWithoutStorage);
        }
    }

        return asset(ltrim($path, '/'));
    }


}
