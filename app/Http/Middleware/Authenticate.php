<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Request;

class Authenticate extends Middleware
{
    public function handle($request, \Closure $next, ...$guards)
    {
        try {
            return parent::handle($request, $next, ...$guards);
        } catch (\Throwable $e) {
            \Log::error('auth.middleware.exception', [
                'guards' => $guards,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function redirectTo($request, $type = null)
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
