<?php

namespace App\Listeners;

// app/Listeners/PersistTenantActivity.php
use App\Models\User;
use App\Events\TenantActivityOccurred;
use App\Models\Api\EmployeeActivityLog;

class PersistTenantActivity
{
    public function handle(TenantActivityOccurred $e): void
    {
        // Skip if tenant doesn’t exist (avoids FK exceptions)
        if (!$e->tenantId || !User::whereKey($e->tenantId)->exists()) {
            \Log::warning('TenantActivitySkipped: unknown tenant filename =PersistTenantActivity', ['tenantId' => $e->tenantId, 'action' => $e->action]);
            return;
        }

        EmployeeActivityLog::create([
            'user_id'     => $e->tenantId,
            'actor_type'  => $e->actorType,
            'actor_id'    => $e->actorId,
            'action'      => $e->action,
            'target_type' => $e->targetType,
            'target_id'   => $e->targetId,
            'old_values'  => $e->oldValues,
            'new_values'  => $e->newValues,
            'ip'          => $e->ip,
            'user_agent'  => $e->userAgent,
        ]);
    }
}
