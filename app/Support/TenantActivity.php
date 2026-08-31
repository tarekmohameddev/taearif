<?php

namespace App\Support;

use Illuminate\Http\Request;
use App\Events\TenantActivityOccurred;

final class TenantActivity
{
    /**
     * Fire a normalized tenant activity event.
     */
    public static function emit(
        Request $request,
        string $action,
        ?string $targetType = null,
        ?int $targetId = null,
        $oldValues = null,
        $newValues = null
    ): void {
        $u = $request->user();

        // Resolve tenant & actor robustly (user table holds both tenant & employee).
        // The actor is not always tenant-aware: `auth:admin` web routes call
        // shouldUse('admin'), so $request->user() can be an Admin, which has no
        // isTenant(). Guard the call the same way the actor line already does.
        $hasTenancy = $u && method_exists($u, 'isTenant');
        $isTenant   = $hasTenancy && $u->isTenant();

        $tenantId  = $hasTenancy ? ($isTenant ? (int)$u->id : (int)$u->tenant_id) : null;
        $actorType = $hasTenancy && !$isTenant ? 'employee' : 'user';
        $actorId   = $u ? (int)$u->id : null;

        // If we can’t resolve tenant, don’t explode; just skip persisting.
        event(new TenantActivityOccurred(
            $tenantId,
            $actorType,
            $actorId,
            $action,
            $targetType,
            $targetId,
            $oldValues,
            $newValues,
            $request->ip(),
            $request->userAgent()
        ));
    }
}
