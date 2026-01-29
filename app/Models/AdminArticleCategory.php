<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdminArticleCategory extends Model
{
    use SoftDeletes;

    protected $table = 'admin_articles_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (AdminArticleCategory $category): void {
            // Auto-generate slug from name if not provided
            $slug = $category->slug;
            if ($slug === null || trim((string) $slug) === '') {
                $base = Str::slug($category->name);
                $slug = $base;
            } else {
                // Sanitize provided slug
                $base = Str::slug($slug);
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

    /**
     * Get the articles for the category.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(AdminArticle::class, 'category_id');
    }

    /**
     * Get the published articles count.
     */
    public function getPublishedArticlesCountAttribute(): int
    {
        return $this->articles()
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->count();
    }
}
