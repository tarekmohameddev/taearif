<?php

namespace App\Http\Resources\Concerns;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\Request;

trait ExposesPropertyBrokerFields
{
    protected function brokerFields(Request $request, Property $property): array
    {
        $user = $request->user();
        if (! $user || ! $user->can('properties.view_broker')) {
            return [];
        }

        $brokerUser = $property->relationLoaded('sourceBroker')
            ? $property->sourceBroker
            : $property->sourceBroker()->first();

        $name = $property->source_broker_name;
        if ($name === null && $brokerUser) {
            $name = trim(($brokerUser->first_name ?? '') . ' ' . ($brokerUser->last_name ?? ''));
            $name = $name !== '' ? $name : $brokerUser->username;
        }

        $phone = $property->source_broker_phone ?? $brokerUser?->phone;

        return [
            'source_broker' => [
                'type' => $property->source_broker_type,
                'id' => $property->source_broker_id,
                'name' => $name,
                'phone' => $phone,
            ],
        ];
    }
}
