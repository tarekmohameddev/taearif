<?php

namespace App\Observers;

use App\Models\Building;
use App\Services\Audit\EntityAuditLogger;
use App\Support\BuildingAuditFields;

class BuildingObserver
{
    public function __construct(
        private readonly EntityAuditLogger $auditLogger,
    ) {}

    public function created(Building $building): void
    {
        $this->auditLogger->logCreated(
            'building',
            $building->id,
            $building->getAttributes(),
            $building->user_id,
        );
    }

    public function updated(Building $building): void
    {
        $this->auditLogger->logFields(
            'building',
            $building->id,
            $building->getOriginal(),
            $building->getAttributes(),
            BuildingAuditFields::TRACKED,
            'updated',
            null,
            $building->user_id,
        );
    }

    public function deleted(Building $building): void
    {
        $this->auditLogger->logDeleted(
            'building',
            $building->id,
            $building->getOriginal(),
            $building->user_id,
        );
    }
}
