<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Language;
use App;

class SetLangMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
     public function handle($request, Closure $next)
     {
         // Allow ?language=ar (or any valid code) to set locale for this request and session (e.g. admin panel)
         $langCode = $request->query('language');
         if ($langCode) {
             $lang = Language::where('code', $langCode)->first();
             if ($lang) {
                 session()->put('lang', $lang->code);
                 app()->setLocale($lang->code);
                 return $next($request);
             }
         }

         if (session()->has('lang')) {
           app()->setLocale(session()->get('lang'));
         } else {
           $defaultLang = Language::where('is_default', 1)->first();
           if (!empty($defaultLang)) {
             app()->setLocale($defaultLang->code);
           }
         }

         return $next($request);
     }
}
