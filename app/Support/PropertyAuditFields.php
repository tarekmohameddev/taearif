<?php

namespace App\Support;

final class PropertyAuditFields
{
    public const STATUS = [
        'unit_status',
        'listing_purpose',
        'publish_status',
        'property_status',
        'purpose',
        'status',
    ];

    public const SENSITIVE = [
        'deed_number',
        'water_meter_number',
        'electricity_meter_number',
        'owner_number',
        'source_broker_type',
        'source_broker_id',
        'source_broker_name',
        'source_broker_phone',
        'project_id',
        'building_id',
        'price',
        'pricePerMeter',
    ];

    public const OTHER = [
        'completion_status',
        'featured',
    ];

    public const TRACKED = [
        ...self::STATUS,
        ...self::SENSITIVE,
        ...self::OTHER,
    ];
}
