<?php

namespace App\Http\Middleware;

use Closure;

class EnsureEmployee
{
    public function handle($request, Closure $next)
    {
        $u = $request->user();
        if (!$u || !$u->isEmployee() || !$u->active) {
            return response()->json(['status' => 'error', 'message' => 'Forbidden (employee only)'], 403);
        }
        return $next($request);
    }
}
