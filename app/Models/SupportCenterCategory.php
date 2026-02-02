<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportCenterCategory extends Model
{
    use SoftDeletes;

    protected $table = 'support_center_categories';

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'icon_image',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (SupportCenterCategory $category): void {
            $slug = $category->slug;
            if ($slug === null || trim((string) $slug) === '') {
                $base = Str::slug($category->name);
                $slug = $base;
            } else {
                $base = Str::slug($slug);
                if ($base === '') {
                    $base = Str::slug($category->name);
                }
                $slug = $base;
            }

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
        return $this->hasMany(SupportCenterArticle::class, 'category_id');
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
