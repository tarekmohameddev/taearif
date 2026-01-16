<?php

namespace App\Jobs;

use App\Services\GoogleAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FetchGoogleAnalyticsViews implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $cacheKey;
    public $startDate;
    public $endDate;
    public $paths;
    public $tenantId;
    public $slugs;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $cacheKey,
        $startDate,
        $endDate,
        array $paths,
        string $tenantId,
        array $slugs
    ) {
        $this->cacheKey = $cacheKey;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->paths = $paths;
        $this->tenantId = $tenantId;
        $this->slugs = $slugs;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(GoogleAnalyticsService $analytics)
    {
        $result = [];
        
        try {
            Log::debug('FetchGoogleAnalyticsViews job started', [
                'cache_key' => $this->cacheKey,
                'tenant_id' => $this->tenantId,
                'slugs_count' => count($this->slugs),
                'paths_count' => count($this->paths),
                'slugs' => $this->slugs,
                'paths' => $this->paths
            ]);
            
            $allData = $analytics->getAllAnalyticsWithFilters(
                $this->startDate,
                $this->endDate,
                [
                    'tenant_ids' => [$this->tenantId],
                    'exclude_empty_tenant' => false,
                    'limit' => count($this->paths) * 10,
                ]
            );

            Log::debug('GA data received', [
                'cache_key' => $this->cacheKey,
                'total_items' => $allData['total_items'] ?? 0,
                'total_views' => $allData['total_views'] ?? 0,
                'data_count' => count($allData['data'] ?? [])
            ]);

            // Build a map of slug => total views
            // Match paths more accurately - check if path exactly matches or contains the slug
            foreach ($allData['data'] ?? [] as $item) {
                $path = $item['path'] ?? '';
                $views = (int) ($item['views'] ?? 0);
                
                if (empty($path) || $views <= 0) {
                    continue;
                }
                
                // Extract slug from path and add to slug view map
                foreach ($this->slugs as $slug) {
                    // Check if path contains the slug (more flexible matching)
                    // Also check exact path matches from the paths array
                    $pathMatches = false;
                    
                    // First, check if path is in our expected paths array (exact match)
                    if (in_array($path, $this->paths)) {
                        $pathMatches = true;
                    } else {
                        // Fallback: check if path contains the slug
                        // Match patterns like /property/slug, /ar/property/slug, /en/property/slug
                        if (strpos($path, $slug) !== false) {
                            $pathMatches = true;
                        }
                    }
                    
                    if ($pathMatches) {
                        $result[$slug] = ($result[$slug] ?? 0) + $views;
                    }
                }
            }
            
            Log::info('GA views job completed', [
                'cache_key' => $this->cacheKey,
                'tenant_id' => $this->tenantId,
                'result_count' => count($result),
                'result' => $result,
                'total_views_sum' => array_sum($result)
            ]);
            
            // Cache the result for 5 minutes (300 seconds)
            Cache::put($this->cacheKey, $result, 300);
            
        } catch (\Exception $e) {
            Log::error('Google Analytics error in background job', [
                'tenant' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'cache_key' => $this->cacheKey
            ]);
            
            // Cache empty result for shorter time to retry sooner
            Cache::put($this->cacheKey, [], 60);
        }
    }
}
