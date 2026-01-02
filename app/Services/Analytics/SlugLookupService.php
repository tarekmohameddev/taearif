<?php

namespace App\Services\Analytics;

use App\Models\Analytics\SlugTenantCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SlugLookupService
{
    /**
     * Batch lookup - collect all slugs, query once
     */
    public function getTenantsForSlugs(array $paths, string $type): array
    {
        // Extract slugs from paths
        $slugs = $this->extractSlugsFromPaths($paths, $type);
        
        if (empty($slugs)) {
            return [];
        }
        
        // Check cache first
        $cached = SlugTenantCache::whereIn('slug', array_map('strtolower', $slugs))
            ->where('slug_type', $type)
            ->pluck('tenant_id', 'slug')
            ->toArray();
            
        // Find missing slugs
        $missing = array_diff(array_map('strtolower', $slugs), array_keys($cached));
        
        if (!empty($missing)) {
            // Query database for missing slugs
            $dbResults = $this->queryDatabaseForSlugs($missing, $type);
            
            // Cache results
            foreach ($dbResults as $slug => $tenantId) {
                if ($tenantId) {
                    SlugTenantCache::cacheSlugTenant($slug, $type, $tenantId);
                    $cached[$slug] = $tenantId;
                }
            }
        }
        
        return $cached;
    }
    
    /**
     * Get tenant for a single slug
     */
    public function getTenantForSlug(string $slug, string $type): ?string
    {
        // Check cache first
        $cached = SlugTenantCache::getTenantForSlug($slug, $type);
        
        if ($cached !== null) {
            return $cached;
        }
        
        // Query database
        $results = $this->queryDatabaseForSlugs([strtolower($slug)], $type);
        
        if (!empty($results)) {
            $tenantId = $results[strtolower($slug)] ?? null;
            if ($tenantId) {
                SlugTenantCache::cacheSlugTenant($slug, $type, $tenantId);
                return $tenantId;
            }
        }
        
        return null;
    }
    
    /**
     * Warm cache for a set of paths
     */
    public function warmCacheForPaths(array $paths): void
    {
        $propertyPaths = array_filter($paths, fn($path) => strpos($path, '/property/') !== false || strpos($path, '/ar/property/') !== false || strpos($path, '/en/property/') !== false);
        $projectPaths = array_filter($paths, fn($path) => strpos($path, '/project/') !== false || strpos($path, '/ar/project/') !== false || strpos($path, '/en/project/') !== false);
        
        if (!empty($propertyPaths)) {
            $this->getTenantsForSlugs($propertyPaths, 'property');
        }
        
        if (!empty($projectPaths)) {
            $this->getTenantsForSlugs($projectPaths, 'project');
        }
    }
    
    /**
     * Clear stale cache entries
     */
    public function clearStaleCache(int $days = 30): int
    {
        return SlugTenantCache::clearStale($days);
    }
    
    /**
     * Extract slugs from paths
     */
    protected function extractSlugsFromPaths(array $paths, string $type): array
    {
        $slugs = [];
        
        foreach ($paths as $path) {
            $parts = array_filter(explode('/', trim($path, '/')));
            
            if (count($parts) < 2) {
                continue;
            }
            
            $slug = null;
            
            // Check if path matches type pattern
            if (in_array($parts[0], ['property', 'project'])) {
                // Path is /property/{slug} or /project/{slug}
                if ($parts[0] === $type) {
                    $slug = $parts[1] ?? null;
                }
            } elseif (count($parts) >= 3 && in_array($parts[1], ['property', 'project'])) {
                // Path is /ar/property/{slug} or /en/project/{slug}
                if ($parts[1] === $type) {
                    $slug = $parts[2] ?? null;
                }
            }
            
            if ($slug) {
                $slugs[] = strtolower($slug);
            }
        }
        
        return array_unique($slugs);
    }
    
    /**
     * Query database for slugs
     */
    protected function queryDatabaseForSlugs(array $slugs, string $type): array
    {
        if (empty($slugs)) {
            return [];
        }
        
        try {
            if ($type === 'property') {
                return DB::table('user_property_contents as upc')
                    ->join('user_properties as up', 'up.id', '=', 'upc.property_id')
                    ->join('users as u', 'u.id', '=', 'up.user_id')
                    ->whereIn(DB::raw('LOWER(upc.slug)'), $slugs)
                    ->pluck('u.username', DB::raw('LOWER(upc.slug)'))
                    ->toArray();
            } elseif ($type === 'project') {
                return DB::table('user_project_contents as upc')
                    ->join('user_projects as p', 'p.id', '=', 'upc.project_id')
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->whereIn(DB::raw('LOWER(upc.slug)'), $slugs)
                    ->pluck('u.username', DB::raw('LOWER(upc.slug)'))
                    ->toArray();
            }
        } catch (\Exception $e) {
            Log::error('Failed to query database for slugs', [
                'type' => $type,
                'slugs_count' => count($slugs),
                'error' => $e->getMessage(),
            ]);
        }
        
        return [];
    }
}

