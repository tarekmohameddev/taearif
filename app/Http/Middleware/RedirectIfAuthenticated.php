<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {

        switch ($guard) {
          case 'admin':
            if (Auth::guard($guard)->check()) {
              return redirect()->route('admin.dashboard');
            }
            break;

            case 'customer':
              if (Auth::guard($guard)->check()) {
                return redirect()->route('customer.dashboard', getParam());
              }
              break;

          default:

            if (Auth::guard($guard)->check()) {
                $user = Auth::user();
                // if (!$user->onboarding_completed) {
                //     return redirect()->route('onboarding.index');
                // }

                return redirect()->route('user-dashboard');
            }
            break;
        }

              return $next($request);
      }
}
