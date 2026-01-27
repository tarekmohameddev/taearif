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
            $slug = trim((string) $category->slug);
            if ($slug === '') {
                // Generate from name if slug is empty
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
}
