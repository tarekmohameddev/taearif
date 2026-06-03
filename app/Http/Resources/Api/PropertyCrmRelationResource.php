<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyCrmRelationResource extends JsonResource
{
    public function toArray($request): array
    {
        $relation = $this->resource;
        $crmRequest = $relation->request;
        $customer = $crmRequest?->customer ?? $relation->customer;

        return [
            'id' => $relation->id,
            'relation_type' => $relation->relation_type,
            'request_id' => $relation->request_id,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone_number ?? null,
            ] : null,
            'employee' => $relation->employee ? [
                'id' => $relation->employee->id,
                'name' => trim(($relation->employee->first_name ?? '') . ' ' . ($relation->employee->last_name ?? '')) ?: $relation->employee->username,
            ] : null,
            'occurred_at' => $relation->occurred_at?->toISOString(),
            'metadata' => $relation->metadata,
        ];
    }
}
