<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PreventSwaggerInProduction
{
    /**
     * Block access to Swagger UI and API docs in production.
     * Allow on local, staging, and dev.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production')) {
            abort(404);
        }

        return $next($request);
    }
}
