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
        // Phase 2: getUser() now consistently returns User|null
        $user = getUser();

        // If user found, check and enforce maintenance mode if needed
        if ($user) {
            if (!$this->membershipService->canControlMaintenanceMode($user)) {
                // Force enable maintenance mode for free package users
                $this->membershipService->enableMaintenanceMode($user);
            }
        }

        return $next($request);
    }
}
