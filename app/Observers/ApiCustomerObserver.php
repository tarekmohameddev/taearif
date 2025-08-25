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

    public function deleted(ApiCustomer $m): void {
        $ctx = AuditContext::data();
        CustomerLog::create(array_merge($ctx, [
            'customer_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'deleted',
            'changes'     => ['before' => $m->getOriginal()],
        ]));
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
