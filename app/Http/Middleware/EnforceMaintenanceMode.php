<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\MembershipService;
use App\Models\Api\GeneralSetting;

class EnforceMaintenanceMode
{
    protected $membershipService;
    
    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = getUser();
        
        // HOTFIX: Ensure $user is actually a User instance before using it
        // getUser() can return View objects in error cases (will be fixed in Phase 2)
        if ($user && ($user instanceof \App\Models\User)) {
            if (!$this->membershipService->canControlMaintenanceMode($user)) {
                // Force enable maintenance mode for free package users
                $this->membershipService->enableMaintenanceMode($user);
            }
        }
        
        return $next($request);
    }
}
