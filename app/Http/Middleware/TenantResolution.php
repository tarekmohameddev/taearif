<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TenantWebsite\TenantResolver;

class TenantResolution
{
    public function handle(Request $request, Closure $next)
    {
        app(TenantResolver::class)->resolve($request);
        return $next($request);
    }
}


