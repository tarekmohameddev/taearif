<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Api\Employee;

class EmployeePermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if ($user instanceof \App\Models\User) {
            return $next($request);
        }

        // If it's an employee, check permission
        if ($user instanceof Employee) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
            return response()->json(['status'=>'error','message'=>'Forbidden'], 403);
        }

        // Not authenticated
        return response()->json(['status'=>'error','message'=>'Unauthenticated'], 401);
    }
}
