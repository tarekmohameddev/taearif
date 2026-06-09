<?php

namespace App\Listeners;

use App\Events\PropertyStatusChanged;
use App\Services\Property\PropertyCrmDealCloseService;
use Illuminate\Support\Facades\Log;

class CloseCrmDealsOnPropertySold
{
    public function __construct(
        private readonly PropertyCrmDealCloseService $crmDealCloseService,
    ) {}

    public function handle(PropertyStatusChanged $event): void
    {
        if ($event->newUnitStatus !== 'sold') {
            return;
        }

        $result = $this->crmDealCloseService->closeDealsForSoldProperty(
            $event->property,
            $event->customerId,
            $event->actorId,
        );

        if (! empty($result['warnings'])) {
            Log::warning('CRM deal close warnings on property sold', [
                'property_id' => $event->property->id,
                'warnings' => $result['warnings'],
            ]);
        }

        if (! empty($result['errors'])) {
            Log::error('CRM deal close errors on property sold', [
                'property_id' => $event->property->id,
                'errors' => $result['errors'],
            ]);
        }
    }
}
