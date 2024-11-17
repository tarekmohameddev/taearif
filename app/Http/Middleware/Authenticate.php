<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function redirectTo($request, $type=null)
    {
        if (!$request->expectsJson()) {
            if (Request::is('admin') || Request::is('admin/*')) {
                return route('admin.login');
            } elseif (Request::route()->getPrefix() == '/{username}' || (Request::route()->getPrefix() == '/user' && Request::getHost() != env('WEBSITE_HOST'))) {
                return route('customer.login', getParam());
            } else {
                return route('user.login');
            }
        }
    }
}
