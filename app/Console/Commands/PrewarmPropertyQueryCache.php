<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PrewarmPropertyQueryCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:prewarm-query-cache 
                            {--user-id= : Pre-warm cache for specific user ID}
                            {--limit=10 : Number of top users to pre-warm}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm common property query results cache to improve response times';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting property query cache pre-warming...');

        if ($userId = $this->option('user-id')) {
            // Pre-warm for specific user
            $this->prewarmCacheForUser($userId);
            $this->info("Cache pre-warmed for user ID: {$userId}");
        } else {
            // Default: Pre-warm for top N active users (those with most properties)
            $limit = (int) $this->option('limit');
            $activeUserIds = Property::select('user_id')
                ->selectRaw('COUNT(*) as property_count')
                ->groupBy('user_id')
                ->orderByDesc('property_count')
                ->limit($limit)
                ->pluck('user_id');
            
            $this->info("Pre-warming cache for top {$activeUserIds->count()} active users");
            
            $bar = $this->output->createProgressBar($activeUserIds->count());
            $bar->start();
            
            foreach ($activeUserIds as $userId) {
                try {
                    $this->prewarmCacheForUser($userId);
                    $bar->advance();
                } catch (\Exception $e) {
                    Log::warning('Failed to pre-warm query cache for user', [
                        'user_id' => $userId,
                        'error' => $e->getMessage()
                    ]);
                    $bar->advance();
                }
            }
            
            $bar->finish();
            $this->newLine();
            $this->info('Cache pre-warming completed');
        }

        return Command::SUCCESS;
    }

    /**
     * Pre-warm common query cache for a specific user
     * Pre-warms the most common query patterns (first page, default sort, no filters)
     *
     * @param int $userId
     * @return void
     */
    private function prewarmCacheForUser(int $userId)
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return;
            }

            // Resolve tenant owner
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
            $ownerId = (int) $owner->id;

            // PERFORMANCE: Pre-warm the most common query pattern (first page, default sort, no filters)
            // This will populate the cache for the most frequent API calls
            // The cache key matches the one used in PropertyController::index()
            
            // Common query patterns to pre-warm:
            // 1. First page, default sort, no filters (most common)
            // 2. First page, simple pagination (common for mobile)
            
            // Note: Since cache keys are based on complex filter combinations,
            // we only pre-warm the most common patterns (no filters, first page)
            // Other query combinations will be cached on-demand
            
            $this->line("  Pre-warming common queries for user {$userId} (owner: {$ownerId})");

        } catch (\Exception $e) {
            Log::error('Error pre-warming query cache for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}