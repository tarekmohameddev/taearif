<?php

namespace App\Http\Middleware;

use App\Services\Payment\PaymentIframeResult;
use Closure;
use Illuminate\Http\Request;

class AllowPaymentIframeEmbedding
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->remove('X-Frame-Options');
        $response->headers->set(
            'Content-Security-Policy',
            PaymentIframeResult::contentSecurityPolicy()
        );

        return $response;
    }
}
