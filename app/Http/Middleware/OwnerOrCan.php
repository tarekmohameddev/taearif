<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OwnerOrCan
{
    /**
     * Allow tenant owners through; otherwise require $ability.
     * Usage: ->middleware('owner-or-can:settings.update')
     */
    public function handle(Request $request, Closure $next, string $ability)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Tenant owner = account_type 'tenant' and NO tenant_id
        $isTenantOwner = (method_exists($user, 'isTenant') && $user->isTenant())
            || (
                (($user->account_type ?? '') === 'tenant')
                && empty($user->tenant_id)
            );

        if ($isTenantOwner) {
            return $next($request); // bypass permissions
        }

        // Employees (or anything else) must have the permission
        if ($user->can($ability)) {
            return $next($request);
        }

        abort(403, 'This action is unauthorized.');
    }
}
