<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class EmployeePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user || !$user instanceof User) {
            return response()->json(['status'=>'error','message'=>'Unauthenticated'], 401);
        }

        // If it's a tenant owner, allow access
        if ($user->isTenant()) {
            return $next($request);
        }

        // If it's an employee, check permission using Spatie
        if ($user->isEmployee()) {
            // Set tenant context for Spatie permissions
            $tenantId = $user->tenantOwnerId();
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
            
            if ($user->hasPermissionTo($permission)) {
                return $next($request);
            }
            return response()->json(['status'=>'error','message'=>'Forbidden'], 403);
        }

        // Not authenticated or invalid account type
        return response()->json(['status'=>'error','message'=>'Unauthenticated'], 401);
    }
}
