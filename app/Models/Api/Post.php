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
        });

        static::creating(function (Post $post): void {
            // Slug: generate from title when empty on create
            $slug = trim((string) $post->slug);
            if ($slug === '') {
                $base = Str::slug($post->title);
                $slug = $base;
                $n = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $n;
                    $n++;
                }
                $post->slug = $slug;
            }
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
}
