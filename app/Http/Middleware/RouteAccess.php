<?php

namespace App\Http\Middleware;

use App\Http\Helpers\UserPermissionHelper;
use App\Models\User;
use App\Models\User\UserPermission;
use Closure;
use Illuminate\Http\Request;

class RouteAccess
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $pages)
    {
        $user = getUser();
        
        // HOTFIX: Ensure $user is actually a User instance
        // getUser() can return View objects in error cases (will be fixed in Phase 2)
        if (!$user || !($user instanceof \App\Models\User)) {
            // If it's a View object, return 404
            if ($user instanceof \Illuminate\View\View) {
                return response($user, 404);
            }
            // Otherwise redirect to appropriate page
            return redirect()->route('front.user.detail.view', getParam());
        }
        
        $currentPackage = UserPermissionHelper::userPackage($user->id);
        $packagePermissions = UserPermissionHelper::packagePermission($user->id);
        $packagePermissions = json_decode($packagePermissions, true);
        $permissions = explode("|", $pages);
        $access = false;

        foreach ($permissions as $permission) {
            if (in_array($permission, $packagePermissions)) {
                $access = true;
            }
        }
        if (!$access) {
            return redirect()->route('front.user.detail.view', getParam());
        }


        return $next($request);
    }
}
