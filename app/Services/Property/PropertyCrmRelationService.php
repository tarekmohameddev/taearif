<?php

namespace App\Services\Property;

use App\Models\Api\Crm\CrmRequest;
use App\Models\Property\PropertyCrmRelation;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PropertyCrmRelationService
{
    public function recordAiMatch(int $propertyId, int $requestId, int $userId, ?int $customerId = null): void
    {
        PropertyCrmRelation::query()->updateOrCreate(
            [
                'property_id' => $propertyId,
                'request_id' => $requestId,
                'relation_type' => PropertyCrmRelation::TYPE_AI_MATCHED,
            ],
            [
                'employee_id' => null,
                'customer_id' => $customerId,
                'occurred_at' => now(),
            ]
        );
    }

    public function manuallyAdd(
        Property $property,
        int $requestId,
        int $employeeId,
        ?int $customerId = null,
    ): PropertyCrmRelation {
        $exists = PropertyCrmRelation::query()
            ->where('property_id', $property->id)
            ->where('request_id', $requestId)
            ->where('relation_type', PropertyCrmRelation::TYPE_MANUALLY_ADDED)
            ->exists();

        if ($exists) {
            throw new ConflictHttpException('This property is already linked to this CRM request.');
        }

        $relation = PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $requestId,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'employee_id' => $employeeId,
            'customer_id' => $customerId,
            'occurred_at' => now(),
        ]);

        try {
            PropertyCrmRelation::create([
                'property_id' => $property->id,
                'request_id' => $requestId,
                'relation_type' => PropertyCrmRelation::TYPE_SENT_TO_CUSTOMER,
                'employee_id' => $employeeId,
                'customer_id' => $customerId,
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to record sent_to_customer relation', [
                'property_id' => $property->id,
                'request_id' => $requestId,
                'error' => $e->getMessage(),
            ]);
        }

        CrmRequest::query()
            ->where('id', $requestId)
            ->where('user_id', $property->user_id)
            ->update(['property_id' => $property->id]);

        return $relation;
    }

    public function counters(int $propertyId): array
    {
        $counts = PropertyCrmRelation::query()
            ->where('property_id', $propertyId)
            ->selectRaw('relation_type, COUNT(*) as total')
            ->groupBy('relation_type')
            ->pluck('total', 'relation_type');

        return [
            'ai_matched' => (int) ($counts[PropertyCrmRelation::TYPE_AI_MATCHED] ?? 0),
            'manually_added' => (int) ($counts[PropertyCrmRelation::TYPE_MANUALLY_ADDED] ?? 0),
            'sent_to_customer' => (int) ($counts[PropertyCrmRelation::TYPE_SENT_TO_CUSTOMER] ?? 0),
        ];
    }

    public function listRelations(int $propertyId, int $perPage = 20): LengthAwarePaginator
    {
        return PropertyCrmRelation::query()
            ->where('property_id', $propertyId)
            ->with([
                'request.customer:id,name,phone_number',
                'employee:id,username,first_name,last_name',
            ])
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }
}
