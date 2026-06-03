<?php

namespace App\Listeners;

use App\Events\PropertyStatusChanged;
use App\Models\Api\Crm\CrmRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CloseCrmDealsOnPropertySold
{
    public function handle(PropertyStatusChanged $event): void
    {
        if ($event->newUnitStatus !== 'sold') {
            return;
        }

        $property = $event->property;

        try {
            $updated = CrmRequest::query()
                ->where('user_id', $property->user_id)
                ->where('property_id', $property->id)
                ->update([
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                Log::info('CRM requests linked to sold property updated', [
                    'property_id' => $property->id,
                    'count' => $updated,
                ]);
            }

            if ($event->customerId) {
                DB::table('crm_requests')
                    ->where('user_id', $property->user_id)
                    ->where('customer_id', $event->customerId)
                    ->whereNull('property_id')
                    ->limit(10)
                    ->update([
                        'property_id' => $property->id,
                        'updated_at' => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to close CRM deals on property sold', [
                'property_id' => $property->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
