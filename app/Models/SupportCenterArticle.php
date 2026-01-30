<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SupportCenterArticle extends Model
{
    use SoftDeletes;

    protected $table = 'support_center_articles';

    protected $fillable = [
        'category_id',
        'admin_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'main_image',
        'status',
        'published_at',
        'cta_enabled',
        'cta_text',
        'cta_url',
        'cta_target_blank',
    ];

    protected $casts = [
        'status' => ArticleStatus::class,
        'published_at' => 'datetime',
        'cta_enabled' => 'boolean',
        'cta_target_blank' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (SupportCenterArticle $article): void {
            $excerpt = trim((string) $article->excerpt);
            if ($excerpt === '') {
                $article->excerpt = Str::limit(strip_tags((string) $article->body), 200);
            }

            $slug = trim((string) $article->slug);
            if ($slug === '') {
                $base = Str::slug($article->title);
                $slug = $base;
            } else {
                $base = Str::slug($slug);
                if ($base === '') {
                    $base = Str::slug($article->title);
                }
                $slug = $base;
            }

            $query = static::where('slug', $slug);
            if ($article->exists) {
                $query->where('id', '!=', $article->id);
            }

            $n = 2;
            $originalSlug = $slug;
            while ($query->exists()) {
                $slug = $originalSlug . '-' . $n;
                $query = static::where('slug', $slug);
                if ($article->exists) {
                    $query->where('id', '!=', $article->id);
                }
                $n++;
            }

            $article->slug = $slug;

            if ($article->status === ArticleStatus::PUBLISHED && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupportCenterCategory::class, 'category_id');
    }

    /**
     * Get the admin that created the article.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    /**
     * Scope a query to only include published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope a query to filter by category.
     */
    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
}
