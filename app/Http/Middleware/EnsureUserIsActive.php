<?php
namespace App\Http\Middleware;

use Closure;

class EnsureUserIsActive
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if ($user && !$user->active) {
            return response()->json(['message' => 'Account is disabled'], 403);
        }
        return $next($request);
    }
}
