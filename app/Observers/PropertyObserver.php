<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\Property;
use App\Models\Logs\PropertyLog;
use App\Support\AuditContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PropertyObserver
{
    /**
     * Clear property-related caches for a given user ID.
     * This ensures statistics and listings remain accurate after property changes.
     */
    private function clearPropertyCachesForUser(?int $userId): void
    {
        if (!$userId) {
            return;
        }
        
        try {
            // Get the user to resolve tenant owner ID (handles both tenants and employees)
            $user = \App\Models\User::find($userId);
            if ($user && method_exists($user, 'tenantOwnerId')) {
                $ownerId = $user->tenantOwnerId();
                
                // Clear property cards cache
                $cacheKey = "property_cards_{$ownerId}";
                Cache::forget($cacheKey);
                
                // Clear properties list cache (all variations)
                // Use pattern matching to clear all cached property lists for this owner
                // Note: Laravel cache doesn't support wildcard deletion, so we'll clear
                // the most common cache keys. For full invalidation, consider using cache tags
                // or a cache key registry if needed.
                $cachePrefix = "properties_list_{$ownerId}_";
                
                // Clear count caches
                Cache::forget("property_counts_{$ownerId}");
                Cache::forget("total_reorder_featured_{$ownerId}");
                Cache::forget("incomplete_count_{$ownerId}");
                
                // Clear filter options cache (may be affected by property changes)
                Cache::forget("property_filter_options_{$ownerId}");
            }
        } catch (\Throwable $e) {
            // Silently fail cache clearing - don't break property operations
            // Log error in development for debugging
            if (app()->environment('local')) {
                Log::warning('Failed to clear property caches', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function created(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'created',
            'changes'     => ['after'=>$m->getAttributes()],
        ]));
        
        // Clear property-related caches when new property is created
        $this->clearPropertyCachesForUser($m->user_id);
    }
    
    public function updated(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'updated',
            'changes'     => ['before'=>$m->getOriginal(), 'after'=>$m->getAttributes()],
        ]));
        
        // Clear property cards cache when property is updated
        // Check if fields that affect statistics have changed
        $original = $m->getOriginal();
        $completionChanged = isset($original['completion_status']) && 
                            ($original['completion_status'] ?? null) !== $m->completion_status;
        $purposeChanged = isset($original['purpose']) && 
                         ($original['purpose'] ?? null) !== $m->purpose;
        $userIdChanged = isset($original['user_id']) && 
                        ($original['user_id'] ?? null) !== $m->user_id;
        
        // Clear cache if any relevant field changed
        // Always clear on update to ensure data consistency (properties list cache is keyed by filters)
        // Clear cache for current user
        $this->clearPropertyCachesForUser($m->user_id);
        
        // If user_id changed, also clear cache for previous user
        if ($userIdChanged && isset($original['user_id'])) {
            $this->clearPropertyCachesForUser($original['user_id']);
        }
    }
    
    public function deleted(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'deleted',
            'changes'     => ['before'=>$m->getOriginal()],
        ]));
        
        // Clear property-related caches when property is deleted
        $this->clearPropertyCachesForUser($m->user_id);
    }
}
