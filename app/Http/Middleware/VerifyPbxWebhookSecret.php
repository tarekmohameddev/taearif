<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the X-Taearif-Secret header sent by the PBX on internal webhook calls.
 * Uses hash_equals to prevent timing-based attacks.
 */
class VerifyPbxWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('calling.internal_secret');
        $provided = $request->header('X-Taearif-Secret', '');

        if (!$expected || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
