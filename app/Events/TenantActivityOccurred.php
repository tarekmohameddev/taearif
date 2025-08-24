<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TenantActivityOccurred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?int $tenantId,
        public readonly string $actorType,
        public readonly ?int $actorId,
        public readonly string $action,
        public readonly ?string $targetType,
        public readonly ?int $targetId,
        public readonly mixed $oldValues = null,   // <-- typed
        public readonly mixed $newValues = null,   // <-- typed
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
    ) {}
}
