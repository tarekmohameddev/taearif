<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Services\PropertyFilterOptionsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PrewarmPropertyFilterCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:prewarm-filter-cache 
                            {--user-id= : Pre-warm cache for specific user ID}
                            {--all : Pre-warm cache for all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-warm property filter options cache to prevent cache misses';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting property filter cache pre-warming...');

        if ($this->option('all')) {
            // Pre-warm for all users (owners/tenants)
            $users = User::whereIn('account_type', ['owner', 'tenant'])
                ->get();
            
            $this->info("Found {$users->count()} users to pre-warm cache for");
            
            $bar = $this->output->createProgressBar($users->count());
            $bar->start();
            
            foreach ($users as $user) {
                try {
                    $this->prewarmCacheForUser($user->id);
                    $bar->advance();
                } catch (\Exception $e) {
                    Log::warning('Failed to pre-warm filter cache for user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    $bar->advance();
                }
            }
            
            $bar->finish();
            $this->newLine();
            $this->info('Cache pre-warming completed for all users');
            
        } elseif ($userId = $this->option('user-id')) {
            // Pre-warm for specific user
            $this->prewarmCacheForUser($userId);
            $this->info("Cache pre-warmed for user ID: {$userId}");
        } else {
            // Default: Pre-warm for active users (those with properties)
            $activeUserIds = Property::distinct('user_id')
                ->pluck('user_id')
                ->take(100); // Limit to first 100 to avoid long execution
            
            $this->info("Pre-warming cache for {$activeUserIds->count()} active users");
            
            $bar = $this->output->createProgressBar($activeUserIds->count());
            $bar->start();
            
            foreach ($activeUserIds as $userId) {
                try {
                    $this->prewarmCacheForUser($userId);
                    $bar->advance();
                } catch (\Exception $e) {
                    Log::warning('Failed to pre-warm filter cache for user', [
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
     * Pre-warm filter cache for a specific user
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

            $allowedUserIds = [$ownerId];
            try {
                $cacheKey = "tenant_employees_{$ownerId}";
                $employeeIds = Cache::remember($cacheKey, 300, function () use ($ownerId) {
                    return User::where('tenant_id', $ownerId)
                        ->where('account_type', 'employee')
                        ->pluck('id')
                        ->toArray();
                });
                $allowedUserIds = array_unique(array_merge($allowedUserIds, $employeeIds));
            } catch (\Throwable $e) {
                // Continue with just owner ID
            }

            // Generate filter options using the service
            // This will populate the cache
            $cacheKey = "property_filter_options_{$ownerId}";
            
            // Use the same cache key and TTL as the controller (1 hour)
            Cache::remember($cacheKey, 3600, function () use ($allowedUserIds) {
                return PropertyFilterOptionsService::generateFilterOptions($allowedUserIds);
            });

        } catch (\Exception $e) {
            Log::error('Error pre-warming filter cache for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

}
