<?php

namespace App\Models\User\RealestateManagement;

use App\Models\User;
use App\Models\Building;
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
        'beds' => 'integer',
        'bath' => 'integer',
        'missing_fields' => 'array',
        'validation_errors' => 'array',
        'completed_at' => 'datetime',
    ];


    protected $fillable = [
        'category_id',
        'region_id',
        'user_id',
        'created_by',
        'payment_method',
        'featured_image',
        'floor_planning_image',
        'video_image',
        'price',
        'pricePerMeter',
        'purpose',
        'property_type',
        'beds',
        'bath',
        'area',
        'size',
        'video_url',
        'virtual_tour',
        'status',
        'property_status',
        'featured',
        'show_reservations',
        'features',
        'faqs',
        'latitude',
        'longitude',
        'project_id',
        'building_id',
        'water_meter_number',
        'electricity_meter_number',
        'deed_number',
        'advertising_license',
        'owner_number',
        'reorder',
        'reorder_featured',
        'completion_status',
        'missing_fields',
        'validation_errors',
        'import_batch_id',
        'completed_at',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function category()
    {
        return $this->belongsTo(ApiUserCategory::class, 'category_id', 'id');
    }

    /**
     * Owners of this unit with their ownership percentage and type.
     */
    public function owners()
    {
        return $this->belongsToMany(\App\Models\User::class, 'property_owners', 'property_id', 'owner_id')
                    ->withPivot('ownership_percentage', 'ownership_type')
                    ->withTimestamps();
    }

    /**
     * Customers this property is assigned to (pivot api_customer_assigned_property).
     */
    public function assignedCustomers()
    {
        return $this->belongsToMany(
            \App\Models\ApiCustomer::class,
            'api_customer_assigned_property',
            'property_id',
            'customer_id'
        )->withTimestamps();
    }

    public static function storeProperty($userId, $request, $featuredImgName, $floorPlanningImage, $videoImage, $featured, $createdBy = null)
    {
        // Ensure default "other" category exists
        $defaultCategory = ApiUserCategory::firstOrCreate(
            ['slug' => 'other'],
            ['name' => 'Other', 'type' => 'property', 'is_active' => 1]
        );

        // Get the tenant owner ID (tenant_id for employees, user_id for tenants)
        $user = User::find($userId);
        $tenantId = $user ? $user->tenantOwnerId() : $userId;

        // Get the creator ID (employee ID if employee created it, otherwise the tenant ID)
        $creatorId = $createdBy ?? auth()->id();

        $reorderFeatured = 0;
        if ($featured) {
            $last = self::where('featured', 1)->max('reorder_featured');
            $reorderFeatured = $last ? $last + 1 : 1;
        }

        // Normalize features to array format
        $features = $request['features'] ?? [];
        if (is_string($features)) {
            // Try to decode as JSON first
            $decoded = json_decode($features, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $features = $decoded;
            } else {
                // If it's a plain string, wrap it in an array
                $features = [$features];
            }
        } elseif (!is_array($features)) {
            // If it's neither string nor array, default to empty array
            $features = [];
        }

        // Normalize missing_fields and validation_errors to arrays
        $missingFields = $request['missing_fields'] ?? null;
        if (is_string($missingFields)) {
            $decoded = json_decode($missingFields, true);
            $missingFields = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
        } elseif (!is_array($missingFields) && !is_null($missingFields)) {
            $missingFields = null;
        }

        $validationErrors = $request['validation_errors'] ?? null;
        if (is_string($validationErrors)) {
            $decoded = json_decode($validationErrors, true);
            $validationErrors = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : null;
        } elseif (!is_array($validationErrors) && !is_null($validationErrors)) {
            $validationErrors = null;
        }

        return self::create([
            'region_id' => $request['region_id'] ?? null,
            'project_id' => $request['project_id'] ?? null,
            'user_id' => $tenantId,
            'created_by' => $creatorId,
            'featured_image' => $featuredImgName,
            'floor_planning_image' => $floorPlanningImage ?? null,
            'video_image' => $videoImage,
            'price' => $request['price'],
            'pricePerMeter' => $request['pricePerMeter'] ?? null,
            'purpose' => $request['purpose'] ?? null,
            'property_type' => $request['property_type'] ?? ($request['type'] ?? null),
            'beds' => $request['beds'] ?? null,
            'bath' => $request['bath'] ?? null,
            'area' => $request['area'],
            'size' => $request['size'] ?? null,
            'featured' => $featured,
            'features' => $features,
            'video_url' => $request['video_url'] ?? null,
            'virtual_tour' => $request['virtual_tour'] ?? null,
            'status' => $request['status'],
            'latitude' => $request['latitude'],
            'longitude' => $request['longitude'],
            'category_id' => $request['category_id'] ?? $defaultCategory->id,
            'payment_method' => $request['payment_method'] ?? null,
            'faqs' => $request['faqs'] ?? [],
            'building_id' => $request['building_id'] ?? null,
            'water_meter_number' => $request['water_meter_number'] ?? null,
            'electricity_meter_number' => $request['electricity_meter_number'] ?? null,
            'deed_number' => $request['deed_number'] ?? null,
            'advertising_license' => $request['advertising_license'] ?? null,
            'owner_number' => $request['owner_number'] ?? null,
            'reorder_featured' => $reorderFeatured,
            'reorder' => 0,
            'show_reservations' => $request['show_reservations'] ?? true,
            'completion_status' => $request['completion_status'] ?? 'complete',
            'missing_fields' => $missingFields,
            'validation_errors' => $validationErrors,
            'import_batch_id' => $request['import_batch_id'] ?? null,
            'completed_at' => $request['completed_at'] ?? null,
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
            'property_type' => $requestData['property_type'] ?? ($requestData['type'] ?? null),
            'beds' => $requestData['beds'] ?? null,
            'bath' => $requestData['bath'] ?? null,
            'area' => $requestData['area'] ?? null,
            'size' => $requestData['size'] ?? $this->size,
            'featured' => $requestData['featured'] ?? 0,
            'video_url' => $requestData['video_url'] ?? null,
            'virtual_tour' => $requestData['virtual_tour'] ?? null,
            'status' => $requestData['status'] ?? 0,
            'features' => $requestData['features'] ?? $this->features,
            'latitude' => $requestData['latitude'] ?? null,
            'longitude' => $requestData['longitude'] ?? null,
            'category_id' => $requestData['category_id'] ?? $this->category_id,
            'payment_method' => $requestData['payment_method'] ?? $this->payment_method  ?? null,
            'faqs' => $requestData['faqs'] ?? $this->faqs,
            'building_id' => $requestData['building_id'] ?? $this->building_id,
            'water_meter_number' => $requestData['water_meter_number'] ?? $this->water_meter_number,
            'electricity_meter_number' => $requestData['electricity_meter_number'] ?? $this->electricity_meter_number,
            'deed_number' => $requestData['deed_number'] ?? $this->deed_number,
            'advertising_license' => $requestData['advertising_license'] ?? $this->advertising_license,
            'owner_number' => $requestData['owner_number'] ?? $this->owner_number,
            'reorder_featured' => $requestData['reorder_featured'] ?? $this->reorder_featured,
            'reorder' => $requestData['reorder'] ?? $this->reorder,
            'show_reservations' => $requestData['show_reservations'] ?? $this->show_reservations,
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

    public function building()
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function rentals()
    {
        return $this->hasMany(\App\Models\Api\Rms\RmRental::class, 'unit_id');
    }

    public function activeRentals()
    {
        return $this->hasMany(\App\Models\Api\Rms\RmRental::class, 'unit_id')->whereIn('status', ['active', 'draft']);
    }

    public function updatePropertyStatus()
    {
        $hasActiveRentals = $this->activeRentals()->exists();
        $this->update(['property_status' => $hasActiveRentals ? 'rented' : 'available']);
    }

    /**
     * Get the owner rentals who have access to this property.
     */
    public function ownerRentals()
    {
        return $this->belongsToMany(\App\Models\OwnerRental::class, 'owner_rental_property', 'property_id', 'owner_rental_id')
                    ->withPivot('assigned_at')
                    ->withTimestamps();
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

    /**
     * Get gallery image URLs as array
     *
     * @return array
     */
    public function getGalleryUrlsAttribute(): array
    {
        if (!$this->relationLoaded('galleryImages')) {
            return [];
        }

        return $this->galleryImages->map(function ($image) {
            return asset($image->image);
        })->toArray();
    }

    /**
     * Get floor planning image URLs as array
     *
     * @return array
     */
    public function getFloorPlanningImageUrlsAttribute(): array
    {
        if (empty($this->floor_planning_image)) {
            return [];
        }

        $images = is_array($this->floor_planning_image)
            ? $this->floor_planning_image
            : [$this->floor_planning_image];

        return array_map(function ($img) {
            return asset($img);
        }, array_filter($images));
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

    /**
     * Scope to exclude incomplete/draft properties from counts
     */
    public function scopeComplete($query)
    {
        return $query->where('completion_status', 'complete');
    }

    /**
     * Scope to get only incomplete properties
     */
    public function scopeIncomplete($query)
    {
        return $query->where('completion_status', 'incomplete');
    }

    /**
     * Scope to get draft properties (incomplete or pending review)
     */
    public function scopeDrafts($query)
    {
        return $query->where('completion_status', '!=', 'complete');
    }

}
