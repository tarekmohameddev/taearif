<?php

namespace App\Listeners;

use App\Events\TenantActivityOccurred;
use App\Models\Api\EmployeeActivityLog;

class WriteTenantActivityLog
{
    public function handle(TenantActivityOccurred $e): void
    {
        EmployeeActivityLog::create([
            'user_id'     => $e->tenantId,
            'actor_type'  => $e->actorType,
            'actor_id'    => $e->actorId,
            'action'      => $e->action,
            'target_type' => $e->target,
            'target_id'   => $e->targetId,
            'old_values'  => $e->old,
            'new_values'  => $e->new,
            'ip'          => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
