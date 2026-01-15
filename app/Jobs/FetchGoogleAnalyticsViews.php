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
            $allData = $analytics->getAllAnalyticsWithFilters(
                $this->startDate,
                $this->endDate,
                [
                    'tenant_ids' => [$this->tenantId],
                    'exclude_empty_tenant' => false,
                    'limit' => count($this->paths) * 10,
                ]
            );

            // Build a map of slug => total views
            foreach ($allData['data'] as $item) {
                $path = $item['path'];
                $views = (int) $item['views'];
                
                // Extract slug from path and add to slug view map
                foreach ($this->slugs as $slug) {
                    if (strpos($path, $slug) !== false) {
                        $result[$slug] = ($result[$slug] ?? 0) + $views;
                    }
                }
            }
            
            // Cache the result for 5 minutes (300 seconds)
            Cache::put($this->cacheKey, $result, 300);
            
        } catch (\Exception $e) {
            Log::error('Google Analytics error in background job', [
                'tenant' => $this->tenantId,
                'error' => $e->getMessage(),
                'cache_key' => $this->cacheKey
            ]);
            
            // Cache empty result for shorter time to retry sooner
            Cache::put($this->cacheKey, [], 60);
        }
    }
}
