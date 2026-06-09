<?php

namespace App\Support;

final class BuildingAuditFields
{
    public const TRACKED = [
        'name',
        'slug',
        'project_id',
        'is_archived',
        'deed_number',
        'deed_image',
        'owner_name',
        'owner_phone',
        'address',
        'city_id',
        'state_id',
        'latitude',
        'longitude',
        'featured_image',
        'image',
        'description',
    ];
}
