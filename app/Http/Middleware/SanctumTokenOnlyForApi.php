<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

/**
 * Ensures Sanctum authenticates API requests only via Bearer token (no web/session guard).
 * Prevents TransientToken usage so that after POST /api/logout, GET /api/user returns 401
 * when the same token is used.
 *
 * Applied only to the 'api' middleware group. Does not change AuthController or production
 * auth flows; only affects which guard Sanctum checks first for API requests.
 */
class SanctumTokenOnlyForApi
{
    public function handle(Request $request, Closure $next)
    {
        $previous = Config::get('sanctum.guard');
        Config::set('sanctum.guard', []);

        try {
            return $next($request);
        } finally {
            Config::set('sanctum.guard', $previous);
        }
    }
}
