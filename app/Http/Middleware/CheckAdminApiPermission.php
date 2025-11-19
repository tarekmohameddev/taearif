<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminApiPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = Auth::guard(config('admin-api.guard'))->user();

        // If no admin is authenticated, return unauthorized
        if (!$admin) {
            return response()->json([
                'status' => 'error',
                'code' => 'AUTH_001',
                'message' => 'Unauthenticated',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 401);
        }

        // Super admin (no role) has all permissions
        if (is_null($admin->role_id)) {
            return $next($request);
        }

        // Check if admin has the required permission
        // Uses Admin model's hasPermission() which checks:
        // 1. Employee-specific permissions (admins.permissions column)
        // 2. Role permissions (roles.permissions column)
        if (!$admin->hasPermission($permission)) {
            return response()->json([
                'status' => 'error',
                'code' => 'AUTH_003',
                'message' => 'You do not have permission to access this resource.',
                'errors' => [
                    'permission' => "Required permission: {$permission}",
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 403);
        }

        return $next($request);
    }
}

