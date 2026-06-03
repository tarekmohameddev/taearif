<?php

namespace App\Models;

use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Building extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'featured_image',
        'owner_name',
        'owner_phone',
        'address',
        'city_id',
        'state_id',
        'latitude',
        'longitude',
        'is_archived',
        'image',
        'deed_number',
        'deed_image',
        'user_id',
        'project_id',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = ['image_url', 'deed_image_url'];

    protected static function booted(): void
    {
        static::saving(function (Building $building): void {
            if (filled($building->slug) || blank($building->name) || ! $building->user_id) {
                return;
            }

            $building->slug = static::generateUniqueSlug(
                $building->name,
                (int) $building->user_id,
                $building->exists ? $building->id : null
            );
        });
    }

    public static function generateUniqueSlug(string $name, int $userId, ?int $exceptId = null): string
    {
        $base = function_exists('make_slug') ? make_slug($name) : Str::slug($name);
        $base = trim((string) $base, '-');
        if ($base === '') {
            $base = 'building';
        }

        $slug = $base;
        $counter = 1;

        while (static::slugExistsForUser($userId, $slug, $exceptId)) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public static function slugExistsForUser(int $userId, string $slug, ?int $exceptId = null): bool
    {
        $query = static::where('user_id', $userId)->where('slug', $slug);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Get the user that owns the building.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project this building belongs to (optional).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the properties for the building.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get the rentals for the building.
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(\App\Models\Api\Rms\RmRental::class);
    }

    /**
     * Get the meters (water and electricity) for the building.
     */
    public function meters(): HasMany
    {
        return $this->hasMany(BuildingMeter::class);
    }

    /**
     * Get the full URL for the building image.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->resolvePublicUrl($this->image);
    }

    /**
     * Get the full URL for the deed image.
     */
    public function getDeedImageUrlAttribute(): ?string
    {
        return $this->resolvePublicUrl($this->deed_image);
    }

    /**
     * Normalize image path on save.
     */
    protected function image(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $this->normalizeImagePath($value)
        );
    }

    protected function deedImage(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn ($value) => $this->normalizeImagePath($value)
        );
    }

    /**
     * Normalize image path to store in buildings directory.
     */
    private function normalizeImagePath($value): ?string
    {
        if (empty($value)) return null;

        // If already absolute URL, extract path
        if (Str::startsWith($value, ['http://', 'https://'])) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);
            $urlHost = parse_url($value, PHP_URL_HOST);
            $urlPath = parse_url($value, PHP_URL_PATH) ?: '';

            // Different host (CDN etc.) => keep absolute
            if ($appHost && $urlHost && $appHost !== $urlHost) {
                return $value;
            }

            // Same host => use path
            $value = $urlPath;
        }

        // Clean and normalize the path
        $path = ltrim($value, '/');
        
        // If path already starts with 'buildings/', return as is
        if (Str::startsWith($path, 'buildings/')) {
            return $path;
        }
        
        // If path starts with 'deeds/' or contains 'deeds/', preserve the structure
        if (Str::startsWith($path, 'deeds/') || Str::contains($path, '/deeds/')) {
            return 'buildings/' . $path;
        }
        
        // For other paths, extract filename and store under buildings directory
        $filename = basename($path);
        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        return 'buildings/' . $filename;
    }

    /**
     * Resolve public URL for image.
     */
    private function resolvePublicUrl(?string $path): ?string
    {
        if (empty($path)) return null;

        // If already absolute URL, just return it
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $candidates = [
            ltrim($path, '/'),
            'buildings/' . basename($path),
        ];

        // Return the first existing file URL
        foreach ($candidates as $rel) {
            if (file_exists(public_path($rel))) {
                return asset($rel);
            }
            // Check storage disk
            $relWithoutStorage = preg_replace('#^storage/#', '', $rel);
            if (Storage::disk('public')->exists($relWithoutStorage)) {
                return asset('storage/' . $relWithoutStorage);
            }
        }

        return asset(ltrim($path, '/'));
    }

    /**
     * Check if building can be deleted (no properties or rentals linked).
     */
    public function canBeDeleted(): bool
    {
        return $this->properties()->count() === 0 && $this->rentals()->count() === 0;
    }
}
