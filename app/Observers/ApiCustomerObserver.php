<?php

// These observers listen to model events so any change—controller, seeder, tinker—gets logged
namespace App\Observers;

use App\Models\ApiCustomer;
use App\Models\Logs\CustomerLog;
use App\Support\AuditContext;

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
    }

    public function restored(ApiCustomer $m): void {
        $ctx = AuditContext::data();
        CustomerLog::create(array_merge($ctx, [
            'customer_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'restored',
        ]));
    }
}
