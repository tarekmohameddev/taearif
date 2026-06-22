<?php

namespace App\Listeners;

use App\Events\PropertyStatusChanged;
use App\Events\TenantActivityOccurred;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Support\Facades\Log;

class NotifyTeamOnStatusChange
{
    public function handle(PropertyStatusChanged $event): void
    {
        $property = $event->property;
        $property->loadMissing('contents');

        $propertyName = $property->first_content?->title;
        $ctx = AuditContext::data();

        $tenantId = $ctx['tenant_id'] ?? null;
        if (! $tenantId) {
            $owner = User::find($property->user_id);
            $tenantId = $owner && method_exists($owner, 'tenantOwnerId')
                ? $owner->tenantOwnerId()
                : $property->user_id;
        }

        $actor = $event->actorId ? User::find($event->actorId) : null;
        $actorType = $actor && $actor->isEmployee() ? 'employee' : 'user';

        event(new TenantActivityOccurred(
            tenantId: $tenantId,
            actorType: $actorType,
            actorId: $event->actorId ?? $ctx['actor_id'] ?? null,
            action: 'property.status_changed',
            targetType: 'property',
            targetId: $property->id,
            oldValues: [
                'unit_status' => $event->oldUnitStatus,
                'property_name' => $propertyName,
            ],
            newValues: array_filter([
                'unit_status' => $event->newUnitStatus,
                'property_name' => $propertyName,
                'reason' => $event->reason,
                'customer_id' => $event->customerId,
            ], fn ($v) => $v !== null),
            ip: $ctx['ip_address'] ?? null,
            userAgent: $ctx['user_agent'] ?? null,
        ));

        Log::info('Property status changed', [
            'property_id' => $property->id,
            'old_unit_status' => $event->oldUnitStatus,
            'new_unit_status' => $event->newUnitStatus,
        ]);
    }
}
