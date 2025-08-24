<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;

class SetTenantForPermissions
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $tenantId = method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : ($user->tenant_id ?: $user->id);
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        }
        return $next($request);
    }
}
