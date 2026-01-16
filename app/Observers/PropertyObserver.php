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
     * Clear property cards cache for a given user ID.
     * This ensures statistics remain accurate after property changes.
     */
    private function clearPropertyCardsCacheForUser(?int $userId): void
    {
        if (!$userId) {
            return;
        }
        
        try {
            // Get the user to resolve tenant owner ID (handles both tenants and employees)
            $user = \App\Models\User::find($userId);
            if ($user && method_exists($user, 'tenantOwnerId')) {
                $ownerId = $user->tenantOwnerId();
                $cacheKey = "property_cards_{$ownerId}";
                Cache::forget($cacheKey);
            }
        } catch (\Throwable $e) {
            // Silently fail cache clearing - don't break property operations
            // Log error in development for debugging
            if (app()->environment('local')) {
                Log::warning('Failed to clear property cards cache', [
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
        
        // Clear property cards cache when new property is created
        $this->clearPropertyCardsCacheForUser($m->user_id);
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
        if ($completionChanged || $purposeChanged || $userIdChanged) {
            // Clear cache for current user
            $this->clearPropertyCardsCacheForUser($m->user_id);
            
            // If user_id changed, also clear cache for previous user
            if ($userIdChanged && isset($original['user_id'])) {
                $this->clearPropertyCardsCacheForUser($original['user_id']);
            }
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
        
        // Clear property cards cache when property is deleted
        $this->clearPropertyCardsCacheForUser($m->user_id);
    }
}
