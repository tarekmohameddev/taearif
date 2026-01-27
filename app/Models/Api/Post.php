<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $table = 'api_posts';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
        'thumbnail_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Post $post): void {
            // Excerpt: auto-generate only when empty; never override manual
            $excerpt = trim((string) $post->excerpt);
            if ($excerpt === '') {
                $post->excerpt = Str::limit(strip_tags((string) $post->content), 200);
            }

            // published_at: set when first published
            if ($post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }

            // Slug: always sanitize and ensure uniqueness
            $slug = trim((string) $post->slug);
            if ($slug === '') {
                // Generate from title if slug is empty
                $base = Str::slug($post->title);
                $slug = $base;
            } else {
                // Sanitize provided slug (remove spaces, special chars, etc.)
                $base = Str::slug($slug);
                // If slug becomes empty after sanitization, fall back to title
                if ($base === '') {
                    $base = Str::slug($post->title);
                }
                $slug = $base;
            }

            // Ensure uniqueness (check within same user's posts)
            $query = static::where('slug', $slug)->where('user_id', $post->user_id);
            if ($post->exists) {
                $query->where('id', '!=', $post->id);
            }
            
            $n = 2;
            $originalSlug = $slug;
            while ($query->exists()) {
                $slug = $originalSlug . '-' . $n;
                $query = static::where('slug', $slug)->where('user_id', $post->user_id);
                if ($post->exists) {
                    $query->where('id', '!=', $post->id);
                }
                $n++;
            }
            
            $post->slug = $slug;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'api_post_categories');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'thumbnail_id');
    }
}
