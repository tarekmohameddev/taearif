<?php

namespace App\Models;

use App\Models\User;
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
        'image',
        'deed_number',
        'deed_image',
        'water_meter_number',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['image_url', 'deed_image_url'];

    /**
     * Get the user that owns the building.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the properties for the building.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
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

        // Extract filename and store under buildings directory
        $path = ltrim($value, '/');
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
     * Check if building can be deleted (no properties linked).
     */
    public function canBeDeleted(): bool
    {
        return $this->properties()->count() === 0;
    }
}
