<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $table = 'api_categories';

    protected $fillable = ['name', 'slug'];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Category $category): void {
            // Slug: always sanitize and ensure uniqueness
            // Handle null, empty string, or whitespace-only slugs
            $slug = $category->slug;
            if ($slug === null || trim((string) $slug) === '') {
                // Generate from name if slug is null or empty
                $base = Str::slug($category->name);
                $slug = $base;
            } else {
                // Sanitize provided slug (remove spaces, special chars, etc.)
                $base = Str::slug($slug);
                // If slug becomes empty after sanitization, fall back to name
                if ($base === '') {
                    $base = Str::slug($category->name);
                }
                $slug = $base;
            }

            // Ensure uniqueness
            $query = static::where('slug', $slug);
            if ($category->exists) {
                $query->where('id', '!=', $category->id);
            }

            $n = 2;
            $originalSlug = $slug;
            while ($query->exists()) {
                $slug = $originalSlug . '-' . $n;
                $query = static::where('slug', $slug);
                if ($category->exists) {
                    $query->where('id', '!=', $category->id);
                }
                $n++;
            }

            $category->slug = $slug;
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'api_post_categories');
    }

    /**
     * Get the slug attribute, generating it from name if null
     */
    public function getSlugAttribute($value)
    {
        // If slug exists, return it
        if ($value !== null && trim($value) !== '') {
            return $value;
        }

        // Generate slug from name if slug is null
        $slug = Str::slug($this->attributes['name'] ?? '');

        // Ensure uniqueness if we're generating on-the-fly
        if ($this->exists && $slug) {
            $query = static::where('slug', $slug)->where('id', '!=', $this->id);
            $n = 2;
            $originalSlug = $slug;
            while ($query->exists()) {
                $slug = $originalSlug . '-' . $n;
                $query = static::where('slug', $slug)->where('id', '!=', $this->id);
                $n++;
            }
        }

        return $slug;
    }
}
