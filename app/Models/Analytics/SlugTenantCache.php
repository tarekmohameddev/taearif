<?php

namespace App\Models\Analytics;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlugTenantCache extends Model
{
    use HasFactory;

    protected $table = 'slug_tenant_cache';

    protected $fillable = ['slug', 'slug_type', 'tenant_id', 'cached_at'];
    
    protected $casts = [
        'cached_at' => 'datetime',
    ];
    
    /**
     * Get tenant ID for a specific slug and type
     */
    public static function getTenantForSlug(string $slug, string $type): ?string
    {
        $cache = self::where('slug', strtolower($slug))
            ->where('slug_type', $type)
            ->first();
            
        return $cache ? $cache->tenant_id : null;
    }
    
    /**
     * Cache a slug-to-tenant mapping
     */
    public static function cacheSlugTenant(string $slug, string $type, string $tenantId): void
    {
        self::updateOrCreate(
            [
                'slug' => strtolower($slug),
                'slug_type' => $type,
            ],
            [
                'tenant_id' => $tenantId,
                'cached_at' => now(),
            ]
        );
    }
    
    /**
     * Clear stale cache entries older than specified days
     */
    public static function clearStale(int $days = 30): int
    {
        return self::where('cached_at', '<', now()->subDays($days))->delete();
    }
}
