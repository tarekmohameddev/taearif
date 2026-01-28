<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Membership;

class RequireActiveMembership
{
	public function handle(Request $request, Closure $next)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthenticated'], 401);
		}

		$owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
		
		// Use centralized MembershipCacheService to avoid cache collision
		$membership = \App\Services\MembershipCacheService::getActiveMembership($owner->id);
		
		$active = $membership && 
				  $membership->expire_date && 
				  now()->lte($membership->expire_date) && 
				  (int) $membership->status === 1;
		
		if (!$active) {
			return response()->json(['message' => 'No active package.'], 402);
		}

		return $next($request);
	}
} 