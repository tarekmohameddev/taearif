<?php

namespace App\Http\Middleware;

use Closure;

class SetAdminLocaleMiddleware
{
    /**
     * Keep the locale selected by SetLangMiddleware for admin requests.
     *
     * SetLangMiddleware validates the requested language and stores it in the
     * session before this middleware runs. Do not overwrite that selection here,
     * otherwise the language switcher can never change the admin away from Arabic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            $locale = app()->getLocale();

            if ($locale && session()->get('lang') !== $locale) {
                session()->put('lang', $locale);
            }
        }

        return $next($request);
    }
}
