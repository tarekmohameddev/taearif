<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\AuditContext;

class PopulateAuditContext
{
    public function handle($request, Closure $next)
    {
        $user = $request->user(); // sanctum user (tenant OR employee in design)

        if ($user && method_exists($user, 'isEmployee')) {
            // Use account_type to determine actor type
            $actorType = $user->isEmployee() ? 'employee' : 'tenant';
            // Use tenantOwnerId() to get the tenant id for both employees and tenants
            $tenantId = $user->tenantOwnerId();
        } else {
            $actorType = 'system';
            $tenantId = null;
        }

        AuditContext::set(
            actorId: $user?->id,
            actorType: $actorType,
            tenantId: $tenantId,
            ip_address: $request->ip(),
            ua: substr($request->userAgent() ?? '', 0, 255)
        );

        return $next($request);
    }
}
