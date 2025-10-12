<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class OwnerRentalAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Find the token in the database
        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Get the owner rental from the token
        $ownerRental = $accessToken->tokenable;

        // Verify it's an OwnerRental model
        if (!$ownerRental || get_class($ownerRental) !== 'App\Models\OwnerRental') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token for owner rental',
            ], 401);
        }

        // Check if owner rental is active
        if (!$ownerRental->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ], 403);
        }

        // Set the authenticated user in the request
        $request->setUserResolver(function () use ($ownerRental) {
            return $ownerRental;
        });

        // Set the owner rental in the Auth facade for this request
        Auth::setUser($ownerRental);

        return $next($request);
    }
}

