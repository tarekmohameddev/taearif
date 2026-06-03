<?php

namespace App\Events;

use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PropertyStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Property $property,
        public readonly ?string $oldUnitStatus,
        public readonly string $newUnitStatus,
        public readonly ?string $reason = null,
        public readonly ?int $customerId = null,
        public readonly ?int $actorId = null,
    ) {}
}
