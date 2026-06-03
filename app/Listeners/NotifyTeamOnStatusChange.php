<?php

namespace App\Listeners;

use App\Events\PropertyStatusChanged;
use App\Events\TenantActivityOccurred;
use Illuminate\Support\Facades\Log;

class NotifyTeamOnStatusChange
{
    public function handle(PropertyStatusChanged $event): void
    {
        $property = $event->property;

        event(new TenantActivityOccurred(
            tenantId: $property->user_id,
            actorType: 'employee',
            actorId: $event->actorId,
            action: 'property.status_changed',
            targetType: 'property',
            targetId: $property->id,
            oldValues: ['unit_status' => $event->oldUnitStatus],
            newValues: [
                'unit_status' => $event->newUnitStatus,
                'reason' => $event->reason,
                'customer_id' => $event->customerId,
            ],
        ));

        Log::info('Property status changed', [
            'property_id' => $property->id,
            'old_unit_status' => $event->oldUnitStatus,
            'new_unit_status' => $event->newUnitStatus,
        ]);
    }
}
