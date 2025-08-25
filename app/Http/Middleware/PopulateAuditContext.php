<?php

namespace App\Http\Middleware;

use Closure;
use App\Support\AuditContext;

class PopulateAuditContext
{
    public function handle($request, Closure $next)
    {
        $user = $request->user(); // sanctum user (tenant OR employee in  design)
        // decide actor_type from  users table: employee vs user(tenant)
        $type = $user?->type === 'employee' ? 'employee' : 'tenant';

        AuditContext::set(
            actorId: $user?->id,
            actorType: $type,
            tenantId: $user?->id ?? null, // or $user->tenant_id if separate tenant id
            ip_address: $request->ip(),
            ua: substr($request->userAgent() ?? '', 0, 255)
        );

        return $next($request);
    }
}
