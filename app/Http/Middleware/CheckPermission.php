<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {

        // if the admin is logged in & he has a role defined then this check will be applied
        if (Auth::guard('admin')->check() && !empty(Auth::guard('admin')->user()->role)) {
            $admin = Auth::guard('admin')->user();
            $rawPermissions = $admin->role->permissions;
            $permissions = is_array($rawPermissions)
                ? $rawPermissions
                : (json_decode($rawPermissions, true) ?: []);
            Log::debug('CheckPermission middleware permissions', [
                'admin_id' => $admin->id ?? null,
                'raw_permissions_type' => gettype($rawPermissions),
                'raw_permissions' => $rawPermissions,
                'permissions' => $permissions,
                'required_permission' => $permission,
            ]);
            if (!in_array($permission, $permissions)) {
                return redirect()->route('admin.dashboard');
            }
        }
       
        return $next($request);
    }
}
