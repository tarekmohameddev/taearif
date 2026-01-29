<?php

namespace App\Http\Middleware;

use Closure;

class SetAdminLocaleMiddleware
{
    /**
     * Ensure admin panel uses Arabic locale so all __() strings resolve from ar.json.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            app()->setLocale('ar');
            if (session()->has('lang') && session()->get('lang') !== 'ar') {
                session()->put('lang', 'ar');
            }
        }

        return $next($request);
    }
}
