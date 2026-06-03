<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\Property;
use App\Models\Logs\PropertyLog;
use App\Support\AuditContext;
use App\Support\CacheInvalidationHelper;
use App\Services\PropertyListCacheVersionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PropertyObserver
{
    /**
     * Clear property-related caches for a given user ID.
     * This ensures statistics and listings remain accurate after property changes.
     * 
     * Senior Rule: "If data can change → it MUST have forget() somewhere"
     * 
     * Uses cache versioning pattern to immediately invalidate all property list caches
     * by incrementing the owner's cache version. This works with file cache driver
     * and doesn't require Redis or wildcard deletion.
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
                
                // Increment cache version to immediately invalidate all property list caches
                // This causes all cached property lists (with any filter combination) to become invalid
                // Old cache entries expire naturally via TTL (5-10 minutes)
                PropertyListCacheVersionService::incrementVersion($ownerId);
                
                // Clear property cards cache
                $cacheKey = "property_cards_{$ownerId}";
                Cache::forget($cacheKey);
                
                // Clear count caches
                Cache::forget("property_counts_{$ownerId}");
                Cache::forget("total_reorder_featured_{$ownerId}");
                Cache::forget("incomplete_count_{$ownerId}");
                
                // Clear filter options cache (may be affected by property changes)
                Cache::forget("property_filter_options_{$ownerId}");
                
                // Clear tenant employees cache (may affect allowed user IDs)
                Cache::forget("tenant_employees_{$ownerId}");
                
                // Clear dashboard caches (property changes affect dashboard stats)
                CacheInvalidationHelper::clearDashboardCaches($userId, $user->username ?? null);
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
        $original = $m->getOriginal();
        $changes = ['before' => $original, 'after' => $m->getAttributes()];

        if (array_key_exists('unit_status', $original)
            && ($original['unit_status'] ?? null) !== $m->unit_status) {
            $changes['unit_status'] = [
                'old' => $original['unit_status'] ?? null,
                'new' => $m->unit_status,
            ];
        }

        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'updated',
            'changes'     => $changes,
        ]));
        
        // Clear property cards cache when property is updated
        // Check if fields that affect statistics have changed
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
