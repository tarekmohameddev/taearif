<?php

// These observers listen to model events so any change—controller, seeder, tinker—gets logged
namespace App\Observers;

use App\Models\ApiCustomer;
use App\Models\Logs\CustomerLog;
use App\Support\AuditContext;
use App\Support\CacheInvalidationHelper;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for ApiCustomer model.
 * 
 * Senior Rule: "If data can change → it MUST have forget() somewhere"
 */
class ApiCustomerObserver
{
    public function created(ApiCustomer $m): void {
        $ctx = AuditContext::data();
        CustomerLog::create(array_merge($ctx, [
            'customer_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'created',
            'changes'     => ['after' => $m->getAttributes()],
        ]));
        
        // Clear customer-related caches
        $this->clearCustomerCaches($m);
    }

    public function updated(ApiCustomer $m): void {
        $ctx = AuditContext::data();
        CustomerLog::create(array_merge($ctx, [
            'customer_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'updated',
            'changes'     => [
                'before' => $m->getOriginal(),
                'after'  => $m->getAttributes(),
            ],
        ]));
        
        // Clear customer-related caches
        $this->clearCustomerCaches($m);
        
        // If user_id changed, clear old user's caches too
        if ($m->isDirty('user_id') && $m->getOriginal('user_id')) {
            $oldUserId = $m->getOriginal('user_id');
            Cache::forget("customers:summary:{$oldUserId}");
            CacheInvalidationHelper::clearDashboardCaches($oldUserId);
        }
    }

    public function deleting(ApiCustomer $m): void {
        try {
            $ctx = AuditContext::data();
            CustomerLog::create(array_merge($ctx, [
                'customer_id' => $m->id,
                'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
                'action'      => 'deleted',
                'changes'     => ['before' => $m->getOriginal()],
            ]));
        } catch (\Exception $e) {
            \Log::error('Failed to log customer deletion', [
                'customer_id' => $m->id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - allow deletion to proceed even if logging fails
        }
        
        // Clear customer-related caches
        $this->clearCustomerCaches($m);
    }

    public function restored(ApiCustomer $m): void {
        $ctx = AuditContext::data();
        CustomerLog::create(array_merge($ctx, [
            'customer_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'restored',
        ]));
        
        // Clear customer-related caches
        $this->clearCustomerCaches($m);
    }
    
    /**
     * Clear caches related to customer data.
     */
    private function clearCustomerCaches(ApiCustomer $customer): void
    {
        $userId = $customer->user_id;
        
        if ($userId) {
            // Clear customer summary cache
            Cache::forget("customers:summary:{$userId}");
            
            // Clear dashboard caches (customer changes affect dashboard stats)
            $user = $customer->user;
            CacheInvalidationHelper::clearDashboardCaches($userId, $user?->username);
        }
    }
}
