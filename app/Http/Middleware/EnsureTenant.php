<?php

namespace App\Http\Middleware;

use Closure;

class EnsureTenant
{
    public function handle($request, Closure $next)
    {
        $u = $request->user();
        if (!$u || !$u->isTenant() || !$u->active) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden (tenant only)'], 403);
        }
        return $next($request);
    }
}
