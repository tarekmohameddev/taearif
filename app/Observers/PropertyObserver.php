<?php

namespace App\Observers;

use App\Models\User\RealestateManagement\Property;
use App\Models\Logs\PropertyLog;
use App\Support\AuditContext;

class PropertyObserver
{
    public function created(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'created',
            'changes'     => ['after'=>$m->getAttributes()],
        ]));
    }
    public function updated(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'updated',
            'changes'     => ['before'=>$m->getOriginal(), 'after'=>$m->getAttributes()],
        ]));
    }
    public function deleted(Property $m): void {
        $ctx = AuditContext::data();
        PropertyLog::create(array_merge($ctx, [
            'property_id' => $m->id,
            'tenant_id'   => $ctx['tenant_id'] ?? $m->user_id,
            'action'      => 'deleted',
            'changes'     => ['before'=>$m->getOriginal()],
        ]));
    }
}
