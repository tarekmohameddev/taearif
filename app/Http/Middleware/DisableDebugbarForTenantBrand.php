<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class DisableDebugbarForTenantBrand
{
    public function handle(Request $request, Closure $next)
    {
        if (! app()->environment('local') && app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return $next($request);
    }
}
